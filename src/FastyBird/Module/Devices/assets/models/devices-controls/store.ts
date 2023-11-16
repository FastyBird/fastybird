import { defineStore } from 'pinia';
import axios from 'axios';
import { Jsona } from 'jsona';
import Ajv from 'ajv/dist/2020';
import { v4 as uuid } from 'uuid';
import get from 'lodash/get';
import isEqual from 'lodash/isEqual';

import exchangeEntitySchema from '../../../../../Library/Metadata/resources/schemas/modules/devices-module/document.device.control.json';
import { DeviceControlEntity as ExchangeEntity, DevicesModuleRoutes as RoutingKeys, ModulePrefix } from '@fastybird/metadata-library';

import { ApiError } from '@/errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '@/jsonapi';
import { useDevices } from '@/models';
import { IDevice } from '@/models/types';

import {
	IDeviceControlsState,
	IDeviceControlsActions,
	IDeviceControlsGetters,
	IDeviceControl,
	IDeviceControlsAddActionPayload,
	IDeviceControlRecordFactoryPayload,
	IDeviceControlResponseModel,
	IDeviceControlResponseJson,
	IDeviceControlsResponseJson,
	IDeviceControlsGetActionPayload,
	IDeviceControlsFetchActionPayload,
	IDeviceControlsSaveActionPayload,
	IDeviceControlsRemoveActionPayload,
	IDeviceControlsSocketDataActionPayload,
	IDeviceControlsUnsetActionPayload,
	IDeviceControlsSetActionPayload,
} from './types';

const jsonSchemaValidator = new Ajv();

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const recordFactory = async (data: IDeviceControlRecordFactoryPayload): Promise<IDeviceControl> => {
	const devicesStore = useDevices();

	let device = devicesStore.findById(data.deviceId);

	if (device === null) {
		if (!(await devicesStore.get({ id: data.deviceId }))) {
			throw new Error("Device for control couldn't be loaded from server");
		}

		device = devicesStore.findById(data.deviceId);

		if (device === null) {
			throw new Error("Device for control couldn't be loaded from store");
		}
	}

	return {
		id: get(data, 'id', uuid().toString()),
		type: { ...{ parent: 'device', entity: 'control' }, ...data.type },

		draft: get(data, 'draft', false),

		name: data.name,

		// Relations
		relationshipNames: ['device'],

		device: {
			id: device.id,
			type: device.type,
		},
	} as IDeviceControl;
};

export const useDeviceControls = defineStore<string, IDeviceControlsState, IDeviceControlsGetters, IDeviceControlsActions>(
	'devices_module_devices_controls',
	{
		state: (): IDeviceControlsState => {
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

				data: {},
			};
		},

		getters: {
			firstLoadFinished: (state: IDeviceControlsState): ((deviceId: string) => boolean) => {
				return (deviceId) => state.firstLoad.includes(deviceId);
			},

			getting: (state: IDeviceControlsState): ((controlId: string) => boolean) => {
				return (controlId) => state.semaphore.fetching.item.includes(controlId);
			},

			fetching: (state: IDeviceControlsState): ((deviceId: string | null) => boolean) => {
				return (deviceId) => (deviceId !== null ? state.semaphore.fetching.items.includes(deviceId) : state.semaphore.fetching.items.length > 0);
			},

			findById: (state: IDeviceControlsState): ((id: string) => IDeviceControl | null) => {
				return (id) => {
					const control = Object.values(state.data).find((control) => control.id === id);

					return control ?? null;
				};
			},

			findByName: (state: IDeviceControlsState): ((device: IDevice, name: string) => IDeviceControl | null) => {
				return (device: IDevice, name) => {
					const control = Object.values(state.data).find((control) => {
						return control.device.id === device.id && control.name.toLowerCase() === name.toLowerCase();
					});

					return control ?? null;
				};
			},

			findForDevice: (state: IDeviceControlsState): ((deviceId: string) => IDeviceControl[]) => {
				return (deviceId: string): IDeviceControl[] => {
					return Object.values(state.data).filter((control) => control.device.id === deviceId);
				};
			},
		},

		actions: {
			/**
			 * Set record from via other store
			 *
			 * @param {IDeviceControlsSetActionPayload} payload
			 */
			async set(payload: IDeviceControlsSetActionPayload): Promise<IDeviceControl> {
				if (payload.data.id && payload.data.id in this.data) {
					const record = await recordFactory({ ...this.data[payload.data.id], ...payload.data });

					return (this.data[record.id] = record);
				}

				const record = await recordFactory(payload.data);

				return (this.data[record.id] = record);
			},

			/**
			 * Remove records for given relation or record by given identifier
			 *
			 * @param {IDeviceControlsUnsetActionPayload} payload
			 */
			unset(payload: IDeviceControlsUnsetActionPayload): void {
				if (typeof payload.device !== 'undefined') {
					Object.keys(this.data).forEach((id) => {
						if (id in this.data && this.data[id].device.id === payload.device?.id) {
							delete this.data[id];
						}
					});

					return;
				} else if (typeof payload.id !== 'undefined') {
					if (payload.id in this.data) {
						delete this.data[payload.id];
					}

					return;
				}

				throw new Error('You have to provide at least device or control id');
			},

			/**
			 * Get one record from server
			 *
			 * @param {IDeviceControlsGetActionPayload} payload
			 */
			async get(payload: IDeviceControlsGetActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.item.includes(payload.id)) {
					return false;
				}

				this.semaphore.fetching.item.push(payload.id);

				try {
					const controlResponse = await axios.get<IDeviceControlResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/controls/${payload.id}`
					);

					const controlResponseModel = jsonApiFormatter.deserialize(controlResponse.data) as IDeviceControlResponseModel;

					this.data[controlResponseModel.id] = await recordFactory({
						...controlResponseModel,
						...{ deviceId: controlResponseModel.device.id },
					});
				} catch (e: any) {
					throw new ApiError('devices-module.device-controls.get.failed', e, 'Fetching control failed.');
				} finally {
					this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
				}

				return true;
			},

			/**
			 * Fetch all records from server
			 *
			 * @param {IDeviceControlsFetchActionPayload} payload
			 */
			async fetch(payload: IDeviceControlsFetchActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.items.includes(payload.device.id)) {
					return false;
				}

				this.semaphore.fetching.items.push(payload.device.id);

				try {
					const controlsResponse = await axios.get<IDeviceControlsResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/controls`
					);

					const controlsResponseModel = jsonApiFormatter.deserialize(controlsResponse.data) as IDeviceControlResponseModel[];

					for (const control of controlsResponseModel) {
						this.data[control.id] = await recordFactory({
							...control,
							...{ deviceId: control.device.id },
						});
					}

					this.firstLoad.push(payload.device.id);
				} catch (e: any) {
					throw new ApiError('devices-module.device-controls.fetch.failed', e, 'Fetching controls failed.');
				} finally {
					this.semaphore.fetching.items = this.semaphore.fetching.items.filter((item) => item !== payload.device.id);
				}

				return true;
			},

			/**
			 * Add new record
			 *
			 * @param {IDeviceControlsAddActionPayload} payload
			 */
			async add(payload: IDeviceControlsAddActionPayload): Promise<IDeviceControl> {
				const newControl = await recordFactory({
					...{
						id: payload?.id,
						type: payload?.type,
						draft: payload?.draft,
						deviceId: payload.device.id,
					},
					...payload.data,
				});

				this.semaphore.creating.push(newControl.id);

				this.data[newControl.id] = newControl;

				if (newControl.draft) {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newControl.id);

					return newControl;
				} else {
					try {
						const createdControl = await axios.post<IDeviceControlResponseJson>(
							`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/controls`,
							jsonApiFormatter.serialize({
								stuff: newControl,
							})
						);

						const createdControlModel = jsonApiFormatter.deserialize(createdControl.data) as IDeviceControlResponseModel;

						this.data[createdControlModel.id] = await recordFactory({
							...createdControlModel,
							...{ deviceId: createdControlModel.device.id },
						});

						return this.data[createdControlModel.id];
					} catch (e: any) {
						// Transformer could not be created on api, we have to remove it from database
						delete this.data[newControl.id];

						throw new ApiError('devices-module.device-controls.create.failed', e, 'Create new control failed.');
					} finally {
						this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newControl.id);
					}
				}
			},

			/**
			 * Save draft record on server
			 *
			 * @param {IDeviceControlsSaveActionPayload} payload
			 */
			async save(payload: IDeviceControlsSaveActionPayload): Promise<IDeviceControl> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('devices-module.device-controls.save.inProgress');
				}

				if (!Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.device-controls.save.failed');
				}

				this.semaphore.updating.push(payload.id);

				const recordToSave = this.data[payload.id];

				try {
					const savedControl = await axios.post<IDeviceControlResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${recordToSave.device.id}/controls`,
						jsonApiFormatter.serialize({
							stuff: recordToSave,
						})
					);

					const savedControlModel = jsonApiFormatter.deserialize(savedControl.data) as IDeviceControlResponseModel;

					this.data[savedControlModel.id] = await recordFactory({
						...savedControlModel,
						...{ deviceId: savedControlModel.device.id },
					});

					return this.data[savedControlModel.id];
				} catch (e: any) {
					throw new ApiError('devices-module.device-controls.save.failed', e, 'Save draft control failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}
			},

			/**
			 * Remove existing record from store and server
			 *
			 * @param {IDeviceControlsRemoveActionPayload} payload
			 */
			async remove(payload: IDeviceControlsRemoveActionPayload): Promise<boolean> {
				if (this.semaphore.deleting.includes(payload.id)) {
					throw new Error('devices-module.device-controls.delete.inProgress');
				}

				if (!Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.device-controls.delete.failed');
				}

				this.semaphore.deleting.push(payload.id);

				const recordToDelete = this.data[payload.id];

				delete this.data[payload.id];

				if (recordToDelete.draft) {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				} else {
					try {
						await axios.delete(`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${recordToDelete.device.id}/controls/${recordToDelete.id}`);
					} catch (e: any) {
						const devicesStore = useDevices();

						const device = devicesStore.findById(recordToDelete.device.id);

						if (device !== null) {
							// Deleting entity on api failed, we need to refresh entity
							await this.get({ device, id: payload.id });
						}

						throw new ApiError('devices-module.device-controls.delete.failed', e, 'Delete control failed.');
					} finally {
						this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
					}
				}

				return true;
			},

			/**
			 * Receive data from sockets
			 *
			 * @param {IDeviceControlsSocketDataActionPayload} payload
			 */
			async socketData(payload: IDeviceControlsSocketDataActionPayload): Promise<boolean> {
				if (
					![
						RoutingKeys.DEVICE_CONTROL_DOCUMENT_REPORTED,
						RoutingKeys.DEVICE_CONTROL_DOCUMENT_CREATED,
						RoutingKeys.DEVICE_CONTROL_DOCUMENT_UPDATED,
						RoutingKeys.DEVICE_CONTROL_DOCUMENT_DELETED,
					].includes(payload.routingKey as RoutingKeys)
				) {
					return false;
				}

				const body: ExchangeEntity = JSON.parse(payload.data);

				const isValid = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema);

				try {
					if (!isValid(body)) {
						return false;
					}
				} catch {
					return false;
				}

				if (payload.routingKey === RoutingKeys.DEVICE_CONTROL_DOCUMENT_DELETED) {
					if (body.id in this.data) {
						delete this.data[body.id];
					}
				} else {
					if (body.id in this.data) {
						const record = await recordFactory({
							...this.data[body.id],
							...{
								id: body.id,
								name: body.name,
								deviceId: body.device,
							},
						});

						if (!isEqual(JSON.parse(JSON.stringify(this.data[body.id])), JSON.parse(JSON.stringify(record)))) {
							this.data[body.id] = record;
						}
					} else {
						const devicesStore = useDevices();

						const device = devicesStore.findById(body.device);

						if (device !== null) {
							try {
								await this.get({
									device,
									id: body.id,
								});
							} catch {
								return false;
							}
						} else {
							try {
								await devicesStore.get({ id: body.device });
							} catch {
								return false;
							}
						}
					}
				}

				return true;
			},
		},
	}
);
