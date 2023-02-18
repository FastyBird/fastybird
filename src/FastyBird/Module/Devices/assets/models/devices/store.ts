import { defineStore } from 'pinia';
import axios from 'axios';
import JsonaService from 'jsona';
import Ajv from 'ajv';
import { v4 as uuid } from 'uuid';
import get from 'lodash/get';

import exchangeEntitySchema from '@fastybird/metadata-library/resources/schemas/modules/devices-module/entity.device.json';
import {
	DeviceEntity as ExchangeEntity,
	DevicePropertyIdentifier,
	DevicesModuleRoutes as RoutingKeys,
	ModulePrefix,
} from '@fastybird/metadata-library';

import { ApiError } from '@/errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '@/jsonapi';
import { useConnectors, useChannels, useDeviceControls, useDeviceProperties } from '@/models';
import {
	IChannelResponseModel,
	IDeviceControlResponseModel,
	IDeviceProperty,
	IDevicePropertyResponseModel,
	IPlainRelation,
} from '@/models/types';

import {
	IDevicesState,
	IDevicesActions,
	IDevicesGetters,
	IDevice,
	IDevicesAddActionPayload,
	IDevicesFetchActionPayload,
	IDevicesGetActionPayload,
	IDeviceRecordFactoryPayload,
	IDevicesRemoveActionPayload,
	IDevicesSetActionPayload,
	IDeviceResponseJson,
	IDeviceResponseModel,
	IDevicesSaveActionPayload,
	IDevicesSocketDataActionPayload,
	IDevicesResponseJson,
	IDevicesEditActionPayload,
} from './types';

const jsonSchemaValidator = new Ajv();

const jsonApiFormatter = new JsonaService({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const recordFactory = async (data: IDeviceRecordFactoryPayload): Promise<IDevice> => {
	const connectorsStore = useConnectors();

	let connector = connectorsStore.findById(data.connectorId);

	if (connector === null) {
		if (!(await connectorsStore.get({ id: data.connectorId }))) {
			throw new Error("Connector for device couldn't be loaded from server");
		}

		connector = connectorsStore.findById(data.connectorId);

		if (connector === null) {
			throw new Error("Connector for device couldn't be loaded from store");
		}
	}

	const record: IDevice = {
		id: get(data, 'id', uuid().toString()),
		type: { ...{ entity: 'device' }, ...data.type },

		draft: get(data, 'draft', false),

		identifier: data.identifier,
		name: get(data, 'name', null),
		comment: get(data, 'comment', null),

		relationshipNames: ['channels', 'properties', 'controls', 'connector', 'parents', 'children'],

		connector: {
			id: connector.id,
			type: connector.type,
		},

		parents: [],
		children: [],

		channels: [],
		controls: [],
		properties: [],

		owner: get(data, 'owner', null),

		get stateProperty(): IDeviceProperty | null {
			const devicePropertiesStore = useDeviceProperties();

			const stateRegex = new RegExp(`^${DevicePropertyIdentifier.STATE}_([0-9]+)$`);

			const stateProperty = devicePropertiesStore
				.findForDevice(this.id)
				.find((property) => property.identifier === DevicePropertyIdentifier.STATE || stateRegex.test(property.identifier));

			return stateProperty ?? null;
		},

		get hasComment(): boolean {
			return this.comment !== null && this.comment !== '';
		},
	};

	record.relationshipNames.forEach((relationName) => {
		if (
			relationName === 'channels' ||
			relationName === 'properties' ||
			relationName === 'controls' ||
			relationName === 'parents' ||
			relationName === 'children'
		) {
			get(data, relationName, []).forEach((relation: any): void => {
				if (get(relation, 'id', null) !== null && get(relation, 'type', null) !== null) {
					(record[relationName] as IPlainRelation[]).push({
						id: get(relation, 'id', null),
						type: get(relation, 'type', null),
					});
				}
			});
		}
	});

	return record;
};

const addChannelsRelations = (device: IDevice, channels: (IChannelResponseModel | IPlainRelation)[]): void => {
	const channelsStore = useChannels();

	channels.forEach((channel) => {
		if ('identifier' in channel) {
			channelsStore.set({
				data: {
					...channel,
					...{
						deviceId: device.id,
					},
				},
			});
		}
	});
};

const addPropertiesRelations = (device: IDevice, properties: (IDevicePropertyResponseModel | IPlainRelation)[]): void => {
	const devicePropertiesStore = useDeviceProperties();

	properties.forEach((property) => {
		if ('identifier' in property) {
			devicePropertiesStore.set({
				data: {
					...property,
					...{
						deviceId: device.id,
					},
				},
			});
		}
	});
};

const addControlsRelations = (device: IDevice, controls: (IDeviceControlResponseModel | IPlainRelation)[]): void => {
	const deviceControlsStore = useDeviceControls();

	controls.forEach((control) => {
		if ('identifier' in control) {
			deviceControlsStore.set({
				data: {
					...control,
					...{
						deviceId: device.id,
					},
				},
			});
		}
	});
};

export const useDevices = defineStore<string, IDevicesState, IDevicesGetters, IDevicesActions>('devices_module_devices', {
	state: (): IDevicesState => {
		return {
			semaphore: {
				fetching: {
					items: false,
					item: [],
				},
				creating: [],
				updating: [],
				deleting: [],
			},

			firstLoad: false,

			data: {},
		};
	},

	getters: {
		firstLoadFinished: (state: IDevicesState): boolean => {
			return state.firstLoad;
		},

		getting: (state: IDevicesState): ((id: string) => boolean) => {
			return (id: string): boolean => state.semaphore.fetching.item.includes(id);
		},

		fetching: (state: IDevicesState): boolean => {
			return state.semaphore.fetching.items;
		},

		findById: (state: IDevicesState): ((id: string) => IDevice | null) => {
			return (id: string): IDevice | null => {
				return id in state.data ? state.data[id] : null;
			};
		},

		findForConnector: (state: IDevicesState): ((connectorId: string) => IDevice[]) => {
			return (connectorId: string): IDevice[] => {
				return Object.values(state.data).filter((device) => device.connector.id === connectorId);
			};
		},
	},

	actions: {
		/**
		 * Set record from via other store
		 *
		 * @param {IDevicesSetActionPayload} payload
		 */
		async set(payload: IDevicesSetActionPayload): Promise<IDevice> {
			const record = await recordFactory(payload.data);

			if ('channels' in payload.data && Array.isArray(payload.data.channels)) {
				addChannelsRelations(record, payload.data.channels);
			}

			if ('properties' in payload.data && Array.isArray(payload.data.properties)) {
				addPropertiesRelations(record, payload.data.properties);
			}

			if ('controls' in payload.data && Array.isArray(payload.data.controls)) {
				addControlsRelations(record, payload.data.controls);
			}

			return (this.data[record.id] = record);
		},

		/**
		 * Get one record from server
		 *
		 * @param {IDevicesGetActionPayload} payload
		 */
		async get(payload: IDevicesGetActionPayload): Promise<boolean> {
			if (this.semaphore.fetching.item.includes(payload.id)) {
				return false;
			}

			this.semaphore.fetching.item.push(payload.id);

			try {
				const deviceResponse = await axios.get<IDeviceResponseJson>(
					`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.id}?include=properties,controls`
				);

				const deviceResponseModel = jsonApiFormatter.deserialize(deviceResponse.data) as IDeviceResponseModel;

				this.data[deviceResponseModel.id] = await recordFactory({
					...deviceResponseModel,
					...{ connectorId: deviceResponseModel.connector.id },
				});

				addControlsRelations(this.data[deviceResponseModel.id], deviceResponseModel.controls);
				addPropertiesRelations(this.data[deviceResponseModel.id], deviceResponseModel.properties);

				if (payload.withChannels) {
					const channelsStore = useChannels();

					await channelsStore.fetch({ device: this.data[deviceResponseModel.id] });
				}
			} catch (e: any) {
				throw new ApiError('devices-module.devices.get.failed', e, 'Fetching device failed.');
			} finally {
				this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
			}

			return true;
		},

		/**
		 * Fetch all records from server
		 *
		 * @param {IDevicesFetchActionPayload} payload
		 */
		async fetch(payload: IDevicesFetchActionPayload): Promise<boolean> {
			if (this.semaphore.fetching.items) {
				return false;
			}

			this.semaphore.fetching.items = true;

			const channelsStore = useChannels();

			try {
				const devicesResponse = await axios.get<IDevicesResponseJson>(
					`/${ModulePrefix.MODULE_DEVICES}/v1/devices?include=properties,controls`
				);

				const devicesResponseModel = jsonApiFormatter.deserialize(devicesResponse.data) as IDeviceResponseModel[];

				for (const device of devicesResponseModel) {
					this.data[device.id] = await recordFactory({
						...device,
						...{ connectorId: device.connector.id },
					});

					addControlsRelations(this.data[device.id], device.controls);
					addPropertiesRelations(this.data[device.id], device.properties);

					if (payload.withChannels) {
						await channelsStore.fetch({ device: this.data[device.id] });
					}
				}

				this.firstLoad = true;
			} catch (e: any) {
				throw new ApiError('devices-module.devices.fetch.failed', e, 'Fetching devices failed.');
			} finally {
				this.semaphore.fetching.items = false;
			}

			return true;
		},

		/**
		 * Add new record
		 *
		 * @param {IDevicesAddActionPayload} payload
		 */
		async add(payload: IDevicesAddActionPayload): Promise<IDevice> {
			const newDevice = await recordFactory({
				...{
					id: payload?.id,
					type: payload?.type,
					draft: payload?.draft,
					connectorId: payload.connector.id,
					parents: payload.parents,
				},
				...payload.data,
			});

			this.semaphore.creating.push(newDevice.id);

			this.data[newDevice.id] = newDevice;

			if (newDevice.draft) {
				this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newDevice.id);

				return newDevice;
			} else {
				try {
					const createdDevice = await axios.post<IDeviceResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices?include=properties,controls`,
						jsonApiFormatter.serialize({
							stuff: newDevice,
						})
					);

					const createdDeviceModel = jsonApiFormatter.deserialize(createdDevice.data) as IDeviceResponseModel;

					this.data[createdDeviceModel.id] = await recordFactory({
						...createdDeviceModel,
						...{ connectorId: createdDeviceModel.connector.id },
					});

					addControlsRelations(this.data[createdDeviceModel.id], createdDeviceModel.controls);
					addPropertiesRelations(this.data[createdDeviceModel.id], createdDeviceModel.properties);

					return this.data[createdDeviceModel.id];
				} catch (e: any) {
					// Record could not be created on api, we have to remove it from database
					delete this.data[newDevice.id];

					throw new ApiError('devices-module.devices.create.failed', e, 'Create new device failed.');
				} finally {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newDevice.id);
				}
			}
		},

		/**
		 * Edit existing record
		 *
		 * @param {IDevicesEditActionPayload} payload
		 */
		async edit(payload: IDevicesEditActionPayload): Promise<IDevice> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('devices-module.devices.update.inProgress');
			}

			if (!Object.keys(this.data).includes(payload.id)) {
				throw new Error('devices-module.devices.update.failed');
			}

			this.semaphore.updating.push(payload.id);

			// Get record stored in database
			const existingRecord = this.data[payload.id];
			// Update with new values
			const updatedRecord = { ...existingRecord, ...payload.data } as IDevice;

			this.data[payload.id] = updatedRecord;

			if (updatedRecord.draft) {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

				return this.data[payload.id];
			} else {
				try {
					const updatedDevice = await axios.patch<IDeviceResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.id}?include=properties,controls`,
						jsonApiFormatter.serialize({
							stuff: updatedRecord,
						})
					);

					const updatedDeviceModel = jsonApiFormatter.deserialize(updatedDevice.data) as IDeviceResponseModel;

					this.data[updatedDeviceModel.id] = await recordFactory({
						...updatedDeviceModel,
						...{ connectorId: updatedDeviceModel.connector.id },
					});

					addControlsRelations(this.data[updatedDeviceModel.id], updatedDeviceModel.controls);
					addPropertiesRelations(this.data[updatedDeviceModel.id], updatedDeviceModel.properties);

					return this.data[updatedDeviceModel.id];
				} catch (e: any) {
					// Updating record on api failed, we need to refresh record
					await this.get({ id: payload.id });

					throw new ApiError('devices-module.devices.update.failed', e, 'Edit device failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}
			}
		},

		/**
		 * Save draft record on server
		 *
		 * @param {IDevicesSaveActionPayload} payload
		 */
		async save(payload: IDevicesSaveActionPayload): Promise<IDevice> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('devices-module.devices.save.inProgress');
			}

			if (!Object.keys(this.data).includes(payload.id)) {
				throw new Error('devices-module.devices.save.failed');
			}

			this.semaphore.updating.push(payload.id);

			const recordToSave = this.data[payload.id];

			try {
				const savedDevice = await axios.post<IDeviceResponseJson>(
					`/${ModulePrefix.MODULE_DEVICES}/v1/devices?include=properties,controls`,
					jsonApiFormatter.serialize({
						stuff: recordToSave,
					})
				);

				const savedDeviceModel = jsonApiFormatter.deserialize(savedDevice.data) as IDeviceResponseModel;

				this.data[savedDeviceModel.id] = await recordFactory({
					...savedDeviceModel,
					...{ connectorId: savedDeviceModel.connector.id },
				});

				addControlsRelations(this.data[savedDeviceModel.id], savedDeviceModel.controls);
				addPropertiesRelations(this.data[savedDeviceModel.id], savedDeviceModel.properties);

				return this.data[savedDeviceModel.id];
			} catch (e: any) {
				throw new ApiError('devices-module.devices.save.failed', e, 'Save draft device failed.');
			} finally {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
			}
		},

		/**
		 * Remove existing record from store and server
		 *
		 * @param {IDevicesRemoveActionPayload} payload
		 */
		async remove(payload: IDevicesRemoveActionPayload): Promise<boolean> {
			if (this.semaphore.deleting.includes(payload.id)) {
				throw new Error('devices-module.devices.delete.inProgress');
			}

			if (!Object.keys(this.data).includes(payload.id)) {
				return true;
			}

			const channelsStore = useChannels();
			const deviceControlsStore = useDeviceControls();
			const devicePropertiesStore = useDeviceProperties();

			this.semaphore.deleting.push(payload.id);

			const recordToDelete = this.data[payload.id];

			delete this.data[payload.id];

			if (recordToDelete.draft) {
				this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);

				channelsStore.unset({ device: recordToDelete });
				deviceControlsStore.unset({ device: recordToDelete });
				devicePropertiesStore.unset({ device: recordToDelete });
			} else {
				try {
					await axios.delete(`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.id}`);

					channelsStore.unset({ device: recordToDelete });
					deviceControlsStore.unset({ device: recordToDelete });
					devicePropertiesStore.unset({ device: recordToDelete });
				} catch (e: any) {
					// Deleting record on api failed, we need to refresh record
					await this.get({ id: payload.id });

					throw new ApiError('devices-module.devices.delete.failed', e, 'Delete device failed.');
				} finally {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				}
			}

			return true;
		},

		/**
		 * Receive data from sockets
		 *
		 * @param {IDevicesSocketDataActionPayload} payload
		 */
		async socketData(payload: IDevicesSocketDataActionPayload): Promise<boolean> {
			if (
				![
					RoutingKeys.DEVICE_ENTITY_REPORTED,
					RoutingKeys.DEVICE_ENTITY_CREATED,
					RoutingKeys.DEVICE_ENTITY_UPDATED,
					RoutingKeys.DEVICE_ENTITY_DELETED,
				].includes(payload.routingKey as RoutingKeys)
			) {
				return false;
			}

			const body: ExchangeEntity = JSON.parse(payload.data);

			const isValid = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema);

			if (!isValid(body)) {
				return false;
			}

			if (payload.routingKey === RoutingKeys.DEVICE_ENTITY_DELETED) {
				if (body.id in this.data) {
					const recordToDelete = this.data[body.id];

					delete this.data[body.id];

					const channelsStore = useChannels();
					const devicePropertiesStore = useDeviceProperties();
					const deviceControlsStore = useDeviceControls();

					channelsStore.unset({ device: recordToDelete });
					devicePropertiesStore.unset({ device: recordToDelete });
					deviceControlsStore.unset({ device: recordToDelete });
				}
			} else {
				if (payload.routingKey === RoutingKeys.DEVICE_ENTITY_UPDATED && this.semaphore.updating.includes(body.id)) {
					return true;
				}

				if (body.id in this.data) {
					this.data[body.id] = await recordFactory({
						...this.data[body.id],
						...{
							name: body.name,
							comment: body.comment,
							connectorId: body.connector,
							owner: body.owner,
						},
					});
				} else {
					await this.get({ id: body.id });
				}
			}

			return true;
		},
	},
});
