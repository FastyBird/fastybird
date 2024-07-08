import { defineStore, Pinia, Store } from 'pinia';
import axios, { AxiosResponse } from 'axios';
import { Jsona } from 'jsona';
import Ajv from 'ajv/dist/2020';
import addFormats from 'ajv-formats';
import { v4 as uuid } from 'uuid';
import get from 'lodash.get';
import isEqual from 'lodash.isequal';

import exchangeDocumentSchema from '../../../resources/schemas/document.device.json';
import {
	DeviceCategory,
	DeviceDocument,
	DevicePropertyIdentifier,
	DevicesModuleRoutes as RoutingKeys,
	ModulePrefix,
} from '@fastybird/metadata-library';

import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { useConnectors, useChannels, useDeviceControls, useDeviceProperties } from '../../models';
import {
	IChannelResponseModel,
	IConnector,
	IDeviceControlResponseModel,
	IDeviceDatabaseRecord,
	IDeviceMeta,
	IDeviceProperty,
	IDevicePropertyResponseModel,
	IDevicesInsertDataActionPayload,
	IDevicesLoadAllRecordsActionPayload,
	IDevicesLoadRecordActionPayload,
	IPlainRelation,
} from '../../models/types';
import { addRecord, getAllRecords, getRecord, removeRecord, DB_TABLE_DEVICES } from '../../utilities/database';

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
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (data: IDeviceRecordFactoryPayload): Promise<IDevice> => {
	const connectorsStore = useConnectors();

	let connector = 'connector' in data ? get(data, 'connector', null) : null;

	let connectorMeta = data.connectorId ? connectorsStore.findMeta(data.connectorId) : null;

	if (connector === null && connectorMeta !== null) {
		connector = {
			id: data.connectorId as string,
			type: connectorMeta,
		};
	}

	if (connector === null) {
		if (!('connectorId' in data)) {
			throw new Error("Connector for device couldn't be loaded from store");
		}

		if (!(await connectorsStore.get({ id: data.connectorId as string, refresh: false }))) {
			throw new Error("Connector for device couldn't be loaded from server");
		}

		connectorMeta = connectorsStore.findMeta(data.connectorId as string);

		if (connectorMeta === null) {
			throw new Error("Connector for device couldn't be loaded from store");
		}

		connector = {
			id: data.connectorId as string,
			type: connectorMeta,
		};
	}

	const record: IDevice = {
		id: get(data, 'id', uuid().toString()),
		type: data.type,

		draft: get(data, 'draft', false),

		category: data.category,
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

const databaseRecordFactory = (record: IDevice): IDeviceDatabaseRecord => {
	return {
		id: record.id,
		type: {
			type: record.type.type,
			source: record.type.source,
			entity: record.type.entity,
		},

		category: record.category,
		identifier: record.identifier,
		name: record.name,
		comment: record.comment,

		relationshipNames: record.relationshipNames.map((name) => name),

		parents: record.parents.map((parent) => ({
			id: parent.id,
			type: { type: parent.type.type, source: parent.type.source, entity: parent.type.entity },
		})),
		children: record.children.map((children) => ({
			id: children.id,
			type: { type: children.type.type, source: children.type.source, entity: children.type.entity },
		})),

		channels: record.channels.map((channel) => ({
			id: channel.id,
			type: { type: channel.type.type, source: channel.type.source, entity: channel.type.entity },
		})),

		controls: record.controls.map((control) => ({
			id: control.id,
			type: { type: control.type.type, source: control.type.source, entity: control.type.entity, parent: control.type.parent },
		})),
		properties: record.properties.map((property) => ({
			id: property.id,
			type: { type: property.type.type, source: property.type.source, entity: property.type.entity, parent: property.type.parent },
		})),

		connector: {
			id: record.connector.id,
			type: {
				type: record.connector.type.type,
				source: record.connector.type.source,
				entity: record.connector.type.entity,
			},
		},

		owner: record.owner,
	};
};

const addChannelsRelations = async (device: IDevice, channels: (IChannelResponseModel | IPlainRelation)[]): Promise<void> => {
	const channelsStore = useChannels();

	for (const channel of channels) {
		if ('identifier' in channel) {
			await channelsStore.set({
				data: {
					...channel,
					...{
						deviceId: device.id,
					},
				},
			});
		}
	}
};

const addPropertiesRelations = async (device: IDevice, properties: (IDevicePropertyResponseModel | IPlainRelation)[]): Promise<void> => {
	const devicePropertiesStore = useDeviceProperties();

	for (const property of properties) {
		if ('identifier' in property) {
			await devicePropertiesStore.set({
				data: {
					...property,
					...{
						deviceId: device.id,
					},
				},
			});
		}
	}
};

const addControlsRelations = async (device: IDevice, controls: (IDeviceControlResponseModel | IPlainRelation)[]): Promise<void> => {
	const deviceControlsStore = useDeviceControls();

	for (const control of controls) {
		if ('identifier' in control) {
			await deviceControlsStore.set({
				data: {
					...control,
					...{
						deviceId: device.id,
					},
				},
			});
		}
	}
};

export const useDevices = defineStore<string, IDevicesState, IDevicesGetters, IDevicesActions>('devices_module_devices', {
	state: (): IDevicesState => {
		return {
			semaphore: {
				fetching: {
					items: [],
					item: [],
				},
				creating: [],
				updating: [],
				deleting: [],
			},

			firstLoad: [],

			data: undefined,
			meta: {},
		};
	},

	getters: {
		firstLoadFinished: (state: IDevicesState): ((connectorId?: IConnector['id'] | null) => boolean) => {
			return (connectorId: IConnector['id'] | null = null): boolean =>
				connectorId !== null ? state.firstLoad.includes(connectorId) : state.firstLoad.includes('all');
		},

		getting: (state: IDevicesState): ((id: IDevice['id']) => boolean) => {
			return (id: IDevice['id']): boolean => state.semaphore.fetching.item.includes(id);
		},

		fetching: (state: IDevicesState): ((connectorId?: IConnector['id'] | null) => boolean) => {
			return (connectorId: IConnector['id'] | null = null): boolean =>
				connectorId !== null ? state.semaphore.fetching.items.includes(connectorId) : state.semaphore.fetching.items.includes('all');
		},

		findById: (state: IDevicesState): ((id: IDevice['id']) => IDevice | null) => {
			return (id: IDevice['id']): IDevice | null => {
				return id in (state.data ?? {}) ? (state.data ?? {})[id] : null;
			};
		},

		findForConnector: (state: IDevicesState): ((connectorId: IConnector['id']) => IDevice[]) => {
			return (connectorId: IConnector['id']): IDevice[] => {
				return Object.values(state.data ?? {}).filter((device: IDevice): boolean => device.connector.id === connectorId);
			};
		},

		findAll: (state: IDevicesState): (() => IDevice[]) => {
			return (): IDevice[] => {
				return Object.values(state.data ?? {});
			};
		},

		findMeta: (state: IDevicesState): ((id: IDevice['id']) => IDeviceMeta | null) => {
			return (id: IDevice['id']): IDeviceMeta | null => {
				return id in state.meta ? state.meta[id] : null;
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
			const record = await storeRecordFactory(payload.data);

			if ('channels' in payload.data && Array.isArray(payload.data.channels)) {
				await addChannelsRelations(record, payload.data.channels);
			}

			if ('properties' in payload.data && Array.isArray(payload.data.properties)) {
				await addPropertiesRelations(record, payload.data.properties);
			}

			if ('controls' in payload.data && Array.isArray(payload.data.controls)) {
				await addControlsRelations(record, payload.data.controls);
			}

			await addRecord<IDeviceDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DEVICES);

			this.meta[record.id] = record.type;

			this.data = this.data ?? {};
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

			const fromDatabase = await this.loadRecord({ id: payload.id });

			if (fromDatabase && payload.refresh === false) {
				return true;
			}

			if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
				this.semaphore.fetching.item.push(payload.id);
			}

			try {
				let deviceResponse: AxiosResponse<IDeviceResponseJson>;

				if (payload.connectorId) {
					deviceResponse = await axios.get<IDeviceResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${payload.connectorId}/devices/${payload.id}`
					);
				} else {
					deviceResponse = await axios.get<IDeviceResponseJson>(`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.id}`);
				}

				const deviceResponseModel = jsonApiFormatter.deserialize(deviceResponse.data) as IDeviceResponseModel;

				this.data = this.data ?? {};
				this.data[deviceResponseModel.id] = await storeRecordFactory({
					...deviceResponseModel,
					...{ connectorId: deviceResponseModel.connector.id },
				});

				await addRecord<IDeviceDatabaseRecord>(databaseRecordFactory(this.data[deviceResponseModel.id]), DB_TABLE_DEVICES);

				this.meta[deviceResponseModel.id] = deviceResponseModel.type;
			} catch (e: any) {
				throw new ApiError('devices-module.devices.get.failed', e, 'Fetching device failed.');
			} finally {
				if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
					this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
				}
			}

			const promises: Promise<boolean>[] = [];

			const propertiesStore = useDeviceProperties();
			promises.push(propertiesStore.fetch({ device: this.data[payload.id] }));

			const controlsStore = useDeviceControls();
			promises.push(controlsStore.fetch({ device: this.data[payload.id] }));

			Promise.all(promises).catch((e: any): void => {
				throw new ApiError('devices-module.devices.get.failed', e, 'Fetching device failed.');
			});

			return true;
		},

		/**
		 * Fetch all records from server
		 *
		 * @param {IDevicesFetchActionPayload} payload
		 */
		async fetch(payload?: IDevicesFetchActionPayload): Promise<boolean> {
			if (this.semaphore.fetching.items.includes(payload?.connectorId ?? 'all')) {
				return false;
			}

			const fromDatabase = await this.loadAllRecords({ connectorId: payload?.connectorId });

			if (fromDatabase && payload?.refresh === false) {
				return true;
			}

			if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
				this.semaphore.fetching.items.push(payload?.connectorId ?? 'all');
			}

			this.firstLoad = this.firstLoad.filter((item) => item !== (payload?.connectorId ?? 'all'));
			this.firstLoad = [...new Set(this.firstLoad)];

			const connectorIds: string[] = [];

			try {
				let devicesResponse: AxiosResponse<IDevicesResponseJson>;

				if (payload?.connectorId) {
					devicesResponse = await axios.get<IDevicesResponseJson>(`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${payload.connectorId}/devices`);
				} else {
					devicesResponse = await axios.get<IDevicesResponseJson>(`/${ModulePrefix.MODULE_DEVICES}/v1/devices`);
				}

				const devicesResponseModel = jsonApiFormatter.deserialize(devicesResponse.data) as IDeviceResponseModel[];

				for (const device of devicesResponseModel) {
					this.data = this.data ?? {};
					this.data[device.id] = await storeRecordFactory({
						...device,
						...{ connectorId: device.connector.id },
					});

					await addRecord<IDeviceDatabaseRecord>(databaseRecordFactory(this.data[device.id]), DB_TABLE_DEVICES);

					this.meta[device.id] = device.type;

					connectorIds.push(device.connector.id);
				}

				if (payload && payload.connectorId) {
					this.firstLoad.push(payload.connectorId);
					this.firstLoad = [...new Set(this.firstLoad)];

					// Get all current IDs from IndexedDB
					const allRecords = await getAllRecords<IDeviceDatabaseRecord>(DB_TABLE_DEVICES);
					const indexedDbIds: string[] = allRecords.filter((record) => record.connector.id === payload.connectorId).map((record) => record.id);

					// Get the IDs from the latest changes
					const serverIds: string[] = Object.keys(this.data ?? {});

					// Find IDs that are in IndexedDB but not in the server response
					const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

					// Remove records that are no longer present on the server
					for (const id of idsToRemove) {
						await removeRecord(id, DB_TABLE_DEVICES);

						delete this.meta[id];
					}
				} else {
					this.firstLoad.push('all');
					this.firstLoad = [...new Set(this.firstLoad)];

					const uniqueConnectorIds = [...new Set(connectorIds)];

					for (const connectorId of uniqueConnectorIds) {
						this.firstLoad.push(connectorId);
						this.firstLoad = [...new Set(this.firstLoad)];
					}

					// Get all current IDs from IndexedDB
					const allRecords = await getAllRecords<IDeviceDatabaseRecord>(DB_TABLE_DEVICES);
					const indexedDbIds: string[] = allRecords.map((record) => record.id);

					// Get the IDs from the latest changes
					const serverIds: string[] = Object.keys(this.data ?? {});

					// Find IDs that are in IndexedDB but not in the server response
					const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

					// Remove records that are no longer present on the server
					for (const id of idsToRemove) {
						await removeRecord(id, DB_TABLE_DEVICES);

						delete this.meta[id];
					}
				}
			} catch (e: any) {
				throw new ApiError('devices-module.devices.fetch.failed', e, 'Fetching devices failed.');
			} finally {
				if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
					this.semaphore.fetching.items = this.semaphore.fetching.items.filter((item) => item !== (payload?.connectorId ?? 'all'));
				}
			}

			const promises: Promise<boolean>[] = [];

			const propertiesStore = useDeviceProperties();
			const controlsStore = useDeviceControls();

			if (payload && payload.connectorId) {
				for (const device of Object.values(this.data ?? {})) {
					if (device.connector.id !== payload.connectorId) {
						continue;
					}

					promises.push(propertiesStore.fetch({ device }));
					promises.push(controlsStore.fetch({ device }));
				}
			} else {
				for (const device of Object.values(this.data ?? {})) {
					promises.push(propertiesStore.fetch({ device }));
					promises.push(controlsStore.fetch({ device }));
				}
			}

			Promise.all(promises).catch((e: any): void => {
				throw new ApiError('devices-module.devices.fetch.failed', e, 'Fetching devices failed.');
			});

			return true;
		},

		/**
		 * Add new record
		 *
		 * @param {IDevicesAddActionPayload} payload
		 */
		async add(payload: IDevicesAddActionPayload): Promise<IDevice> {
			const newDevice = await storeRecordFactory({
				...{
					id: payload?.id,
					type: payload?.type,
					category: DeviceCategory.GENERIC,
					draft: payload?.draft,
					connectorId: payload.connector.id,
					parents: payload.parents,
				},
				...payload.data,
			});

			this.semaphore.creating.push(newDevice.id);

			this.data = this.data ?? {};
			this.data[newDevice.id] = newDevice;

			if (newDevice.draft) {
				this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newDevice.id);

				return newDevice;
			} else {
				try {
					const createdDevice = await axios.post<IDeviceResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices`,
						jsonApiFormatter.serialize({
							stuff: newDevice,
						})
					);

					const createdDeviceModel = jsonApiFormatter.deserialize(createdDevice.data) as IDeviceResponseModel;

					this.data[createdDeviceModel.id] = await storeRecordFactory({
						...createdDeviceModel,
						...{ connectorId: createdDeviceModel.connector.id },
					});

					await addRecord<IDeviceDatabaseRecord>(databaseRecordFactory(this.data[createdDeviceModel.id]), DB_TABLE_DEVICES);

					this.meta[createdDeviceModel.id] = createdDeviceModel.type;
				} catch (e: any) {
					// Record could not be created on api, we have to remove it from database
					delete this.data[newDevice.id];

					throw new ApiError('devices-module.devices.create.failed', e, 'Create new device failed.');
				} finally {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newDevice.id);
				}

				const promises: Promise<boolean>[] = [];

				const propertiesStore = useDeviceProperties();
				promises.push(propertiesStore.fetch({ device: this.data[newDevice.id] }));

				const controlsStore = useDeviceControls();
				promises.push(controlsStore.fetch({ device: this.data[newDevice.id] }));

				Promise.all(promises).catch((e: any): void => {
					throw new ApiError('devices-module.devices.create.failed', e, 'Create new device failed.');
				});

				return this.data[newDevice.id];
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

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
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
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.id}`,
						jsonApiFormatter.serialize({
							stuff: updatedRecord,
						})
					);

					const updatedDeviceModel = jsonApiFormatter.deserialize(updatedDevice.data) as IDeviceResponseModel;

					this.data[updatedDeviceModel.id] = await storeRecordFactory({
						...updatedDeviceModel,
						...{ connectorId: updatedDeviceModel.connector.id },
					});

					await addRecord<IDeviceDatabaseRecord>(databaseRecordFactory(this.data[updatedDeviceModel.id]), DB_TABLE_DEVICES);

					this.meta[updatedDeviceModel.id] = updatedDeviceModel.type;
				} catch (e: any) {
					// Updating record on api failed, we need to refresh record
					await this.get({ id: payload.id });

					throw new ApiError('devices-module.devices.update.failed', e, 'Edit device failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}

				const promises: Promise<boolean>[] = [];

				const propertiesStore = useDeviceProperties();
				promises.push(propertiesStore.fetch({ device: this.data[payload.id] }));

				const controlsStore = useDeviceControls();
				promises.push(controlsStore.fetch({ device: this.data[payload.id] }));

				Promise.all(promises).catch((e: any): void => {
					throw new ApiError('devices-module.devices.update.failed', e, 'Edit device failed.');
				});

				return this.data[payload.id];
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

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				throw new Error('devices-module.devices.save.failed');
			}

			this.semaphore.updating.push(payload.id);

			const recordToSave = this.data[payload.id];

			try {
				const savedDevice = await axios.post<IDeviceResponseJson>(
					`/${ModulePrefix.MODULE_DEVICES}/v1/devices`,
					jsonApiFormatter.serialize({
						stuff: recordToSave,
					})
				);

				const savedDeviceModel = jsonApiFormatter.deserialize(savedDevice.data) as IDeviceResponseModel;

				this.data[savedDeviceModel.id] = await storeRecordFactory({
					...savedDeviceModel,
					...{ connectorId: savedDeviceModel.connector.id },
				});

				await addRecord<IDeviceDatabaseRecord>(databaseRecordFactory(this.data[savedDeviceModel.id]), DB_TABLE_DEVICES);

				this.meta[savedDeviceModel.id] = savedDeviceModel.type;
			} catch (e: any) {
				throw new ApiError('devices-module.devices.save.failed', e, 'Save draft device failed.');
			} finally {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
			}

			const promises: Promise<boolean>[] = [];

			const propertiesStore = useDeviceProperties();
			promises.push(propertiesStore.fetch({ device: this.data[payload.id] }));

			const controlsStore = useDeviceControls();
			promises.push(controlsStore.fetch({ device: this.data[payload.id] }));

			Promise.all(promises).catch((e: any): void => {
				throw new ApiError('devices-module.devices.save.failed', e, 'Save draft device failed.');
			});

			return this.data[payload.id];
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

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				return true;
			}

			const channelsStore = useChannels();
			const deviceControlsStore = useDeviceControls();
			const devicePropertiesStore = useDeviceProperties();

			this.semaphore.deleting.push(payload.id);

			const recordToDelete = this.data[payload.id];

			delete this.data[payload.id];

			await removeRecord(payload.id, DB_TABLE_DEVICES);

			delete this.meta[payload.id];

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
					RoutingKeys.DEVICE_DOCUMENT_REPORTED,
					RoutingKeys.DEVICE_DOCUMENT_CREATED,
					RoutingKeys.DEVICE_DOCUMENT_UPDATED,
					RoutingKeys.DEVICE_DOCUMENT_DELETED,
				].includes(payload.routingKey as RoutingKeys)
			) {
				return false;
			}

			const body: DeviceDocument = JSON.parse(payload.data);

			const isValid = jsonSchemaValidator.compile<DeviceDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(body)) {
					return false;
				}
			} catch {
				return false;
			}

			if (payload.routingKey === RoutingKeys.DEVICE_DOCUMENT_DELETED) {
				await removeRecord(body.id, DB_TABLE_DEVICES);

				delete this.meta[body.id];

				if (this.data && body.id in this.data) {
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
				if (payload.routingKey === RoutingKeys.DEVICE_DOCUMENT_UPDATED && this.semaphore.updating.includes(body.id)) {
					return true;
				}

				if (this.data && body.id in this.data) {
					const record = await storeRecordFactory({
						...this.data[body.id],
						...{
							category: body.category,
							name: body.name,
							comment: body.comment,
							connectorId: body.connector,
							owner: body.owner,
						},
					});

					if (!isEqual(JSON.parse(JSON.stringify(this.data[body.id])), JSON.parse(JSON.stringify(record)))) {
						this.data[body.id] = record;

						await addRecord<IDeviceDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DEVICES);

						this.meta[record.id] = record.type;
					}
				} else {
					try {
						await this.get({ id: body.id });
					} catch {
						return false;
					}
				}
			}

			return true;
		},

		/**
		 * Insert data from SSR
		 *
		 * @param {IDevicesInsertDataActionPayload} payload
		 */
		async insertData(payload: IDevicesInsertDataActionPayload): Promise<boolean> {
			this.data = this.data ?? {};

			let documents: DeviceDocument[] = [];

			if (Array.isArray(payload.data)) {
				documents = payload.data;
			} else {
				documents = [payload.data];
			}

			const connectorIds = [];

			for (const doc of documents) {
				const isValid = jsonSchemaValidator.compile<DeviceDocument>(exchangeDocumentSchema);

				try {
					if (!isValid(doc)) {
						return false;
					}
				} catch {
					return false;
				}

				const record = await storeRecordFactory({
					...this.data[doc.id],
					...{
						id: doc.id,
						type: {
							type: doc.type,
							source: doc.source,
							entity: 'device',
						},
						category: doc.category,
						identifier: doc.identifier,
						name: doc.name,
						comment: doc.comment,
						connectorId: doc.connector,
						owner: doc.owner,
					},
				});

				if (documents.length === 1) {
					this.data[doc.id] = record;
				}

				await addRecord<IDeviceDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DEVICES);

				this.meta[record.id] = record.type;

				connectorIds.push(doc.connector);
			}

			if (documents.length > 1) {
				const uniqueConnectorIds = [...new Set(connectorIds)];

				if (uniqueConnectorIds.length > 1) {
					this.firstLoad.push('all');
					this.firstLoad = [...new Set(this.firstLoad)];
				}

				for (const connectorId of uniqueConnectorIds) {
					this.firstLoad.push(connectorId);
					this.firstLoad = [...new Set(this.firstLoad)];
				}
			}

			return true;
		},

		/**
		 * Load record from database
		 *
		 * @param {IDevicesLoadRecordActionPayload} payload
		 */
		async loadRecord(payload: IDevicesLoadRecordActionPayload): Promise<boolean> {
			const record = await getRecord<IDeviceDatabaseRecord>(payload.id, DB_TABLE_DEVICES);

			if (record) {
				this.data = this.data ?? {};
				this.data[payload.id] = await storeRecordFactory(record);

				return true;
			}

			return false;
		},

		/**
		 * Load records from database
		 *
		 * @param {IDevicesLoadAllRecordsActionPayload} payload
		 */
		async loadAllRecords(payload?: IDevicesLoadAllRecordsActionPayload): Promise<boolean> {
			const records = await getAllRecords<IDeviceDatabaseRecord>(DB_TABLE_DEVICES);

			this.data = this.data ?? {};

			for (const record of records) {
				if (payload?.connectorId && payload?.connectorId !== record?.connector.id) {
					continue;
				}

				this.data[record.id] = await storeRecordFactory(record);
			}

			return true;
		},
	},
});

export const registerDevicesStore = (pinia: Pinia): Store => {
	return useDevices(pinia);
};
