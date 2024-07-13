import { ActionRoutes, ExchangeCommand, DeviceControlDocument, DevicesModuleRoutes as RoutingKeys, ModulePrefix } from '@fastybird/metadata-library';
import { useWampV1Client } from '@fastybird/vue-wamp-v1';
import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios from 'axios';
import { Jsona } from 'jsona';
import get from 'lodash.get';
import isEqual from 'lodash.isequal';
import { defineStore, Pinia, Store } from 'pinia';
import { v4 as uuid } from 'uuid';

import exchangeDocumentSchema from '../../../resources/schemas/document.device.control.json';

import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { useDevices } from '../../models';
import { IDevice } from '../devices/types';
import { addRecord, getAllRecords, getRecord, removeRecord, DB_TABLE_DEVICES_CONTROLS } from '../../utilities/database';

import {
	IDeviceControl,
	IDeviceControlDatabaseRecord,
	IDeviceControlMeta,
	IDeviceControlRecordFactoryPayload,
	IDeviceControlResponseJson,
	IDeviceControlResponseModel,
	IDeviceControlsActions,
	IDeviceControlsAddActionPayload,
	IDeviceControlsFetchActionPayload,
	IDeviceControlsGetActionPayload,
	IDeviceControlsGetters,
	IDeviceControlsInsertDataActionPayload,
	IDeviceControlsLoadAllRecordsActionPayload,
	IDeviceControlsLoadRecordActionPayload,
	IDeviceControlsRemoveActionPayload,
	IDeviceControlsResponseJson,
	IDeviceControlsSaveActionPayload,
	IDeviceControlsSetActionPayload,
	IDeviceControlsSocketDataActionPayload,
	IDeviceControlsState,
	IDeviceControlsTransmitCommandActionPayload,
	IDeviceControlsUnsetActionPayload,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (data: IDeviceControlRecordFactoryPayload): Promise<IDeviceControl> => {
	const devicesStore = useDevices();

	let device = 'device' in data ? get(data, 'device', null) : null;

	let deviceMeta = data.deviceId ? devicesStore.findMeta(data.deviceId) : null;

	if (device === null && deviceMeta !== null) {
		device = {
			id: data.deviceId as string,
			type: deviceMeta,
		};
	}

	if (device === null) {
		if (!('deviceId' in data)) {
			throw new Error("Device for control couldn't be loaded from store");
		}

		if (!(await devicesStore.get({ id: data.deviceId as string, refresh: false }))) {
			throw new Error("Device for control couldn't be loaded from server");
		}

		deviceMeta = devicesStore.findMeta(data.deviceId as string);

		if (deviceMeta === null) {
			throw new Error("Device for control couldn't be loaded from store");
		}

		device = {
			id: data.deviceId as string,
			type: deviceMeta,
		};
	}

	return {
		id: get(data, 'id', uuid().toString()),
		type: data.type,

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

const databaseRecordFactory = (record: IDeviceControl): IDeviceControlDatabaseRecord => {
	return {
		id: record.id,
		type: {
			type: record.type.type,
			source: record.type.source,
			entity: record.type.entity,
			parent: record.type.parent,
		},

		name: record.name,

		relationshipNames: record.relationshipNames.map((name) => name),

		device: {
			id: record.device.id,
			type: {
				type: record.device.type.type,
				source: record.device.type.source,
				entity: record.device.type.entity,
			},
		},
	};
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

				data: undefined,
				meta: {},
			};
		},

		getters: {
			getting: (state: IDeviceControlsState): ((id: IDeviceControl['id']) => boolean) => {
				return (id: IDeviceControl['id']): boolean => state.semaphore.fetching.item.includes(id);
			},

			fetching: (state: IDeviceControlsState): ((deviceId: IDevice['id'] | null) => boolean) => {
				return (deviceId: IDevice['id'] | null): boolean =>
					deviceId !== null ? state.semaphore.fetching.items.includes(deviceId) : state.semaphore.fetching.items.length > 0;
			},

			findById: (state: IDeviceControlsState): ((id: IDeviceControl['id']) => IDeviceControl | null) => {
				return (id: IDeviceControl['id']): IDeviceControl | null => {
					const control: IDeviceControl | undefined = Object.values(state.data ?? {}).find((control: IDeviceControl): boolean => control.id === id);

					return control ?? null;
				};
			},

			findByName: (state: IDeviceControlsState): ((device: IDevice, name: IDeviceControl['name']) => IDeviceControl | null) => {
				return (device: IDevice, name: IDeviceControl['name']): IDeviceControl | null => {
					const control: IDeviceControl | undefined = Object.values(state.data ?? {}).find((control: IDeviceControl): boolean => {
						return control.device.id === device.id && control.name.toLowerCase() === name.toLowerCase();
					});

					return control ?? null;
				};
			},

			findForDevice: (state: IDeviceControlsState): ((deviceId: IDevice['id']) => IDeviceControl[]) => {
				return (deviceId: IDevice['id']): IDeviceControl[] => {
					return Object.values(state.data ?? {}).filter((control: IDeviceControl): boolean => control.device.id === deviceId);
				};
			},

			findMeta: (state: IDeviceControlsState): ((id: IDeviceControl['id']) => IDeviceControlMeta | null) => {
				return (id: IDeviceControl['id']): IDeviceControlMeta | null => {
					return id in state.meta ? state.meta[id] : null;
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
				if (this.data && payload.data.id && payload.data.id in this.data) {
					const record = await storeRecordFactory({ ...this.data[payload.data.id], ...payload.data });

					return (this.data[record.id] = record);
				}

				const record = await storeRecordFactory(payload.data);

				await addRecord<IDeviceControlDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DEVICES_CONTROLS);

				this.meta[record.id] = record.type;

				this.data = this.data ?? {};
				return (this.data[record.id] = record);
			},

			/**
			 * Remove records for given relation or record by given identifier
			 *
			 * @param {IDeviceControlsUnsetActionPayload} payload
			 */
			async unset(payload: IDeviceControlsUnsetActionPayload): Promise<void> {
				if (!this.data) {
					return;
				}

				if (payload.device !== undefined) {
					const items = this.findForDevice(payload.device.id);

					for (const item of items) {
						if (item.id in (this.data ?? {})) {
							await removeRecord(item.id, DB_TABLE_DEVICES_CONTROLS);

							delete this.meta[item.id];

							delete (this.data ?? {})[item.id];
						}
					}

					return;
				} else if (payload.id !== undefined) {
					await removeRecord(payload.id, DB_TABLE_DEVICES_CONTROLS);

					delete this.meta[payload.id];

					delete this.data[payload.id];

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

				const fromDatabase = await this.loadRecord({ id: payload.id });

				if (fromDatabase && payload.refresh === false) {
					return true;
				}

				this.semaphore.fetching.item.push(payload.id);

				try {
					const controlResponse = await axios.get<IDeviceControlResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/controls/${payload.id}`
					);

					const controlResponseModel = jsonApiFormatter.deserialize(controlResponse.data) as IDeviceControlResponseModel;

					this.data = this.data ?? {};
					this.data[controlResponseModel.id] = await storeRecordFactory({
						...controlResponseModel,
						...{ deviceId: controlResponseModel.device.id },
					});

					await addRecord<IDeviceControlDatabaseRecord>(databaseRecordFactory(this.data[controlResponseModel.id]), DB_TABLE_DEVICES_CONTROLS);

					this.meta[controlResponseModel.id] = controlResponseModel.type;
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

				const fromDatabase = await this.loadAllRecords({ device: payload.device });

				if (fromDatabase && payload?.refresh === false) {
					return true;
				}

				this.semaphore.fetching.items.push(payload.device.id);

				try {
					const controlsResponse = await axios.get<IDeviceControlsResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/controls`
					);

					const controlsResponseModel = jsonApiFormatter.deserialize(controlsResponse.data) as IDeviceControlResponseModel[];

					for (const control of controlsResponseModel) {
						this.data = this.data ?? {};
						this.data[control.id] = await storeRecordFactory({
							...control,
							...{ deviceId: control.device.id },
						});

						await addRecord<IDeviceControlDatabaseRecord>(databaseRecordFactory(this.data[control.id]), DB_TABLE_DEVICES_CONTROLS);

						this.meta[control.id] = control.type;
					}

					// Get all current IDs from IndexedDB
					const allRecords = await getAllRecords<IDeviceControlDatabaseRecord>(DB_TABLE_DEVICES_CONTROLS);
					const indexedDbIds: string[] = allRecords.filter((record) => record.device.id === payload.device.id).map((record) => record.id);

					// Get the IDs from the latest changes
					const serverIds: string[] = Object.keys(this.data ?? {});

					// Find IDs that are in IndexedDB but not in the server response
					const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

					// Remove records that are no longer present on the server
					for (const id of idsToRemove) {
						await removeRecord(id, DB_TABLE_DEVICES_CONTROLS);

						delete this.meta[id];
					}
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
				const newControl = await storeRecordFactory({
					...{
						id: payload?.id,
						type: payload?.type,
						draft: payload?.draft,
						deviceId: payload.device.id,
					},
					...payload.data,
				});

				this.semaphore.creating.push(newControl.id);

				this.data = this.data ?? {};
				this.data[newControl.id] = newControl;

				if (newControl.draft) {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newControl.id);

					return newControl;
				} else {
					const devicesStore = useDevices();

					const device = devicesStore.findById(payload.device.id);

					if (device === null) {
						this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newControl.id);

						throw new Error('devices-module.device-controls.get.failed');
					}

					try {
						const createdControl = await axios.post<IDeviceControlResponseJson>(
							`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/controls`,
							jsonApiFormatter.serialize({
								stuff: newControl,
							})
						);

						const createdControlModel = jsonApiFormatter.deserialize(createdControl.data) as IDeviceControlResponseModel;

						this.data[createdControlModel.id] = await storeRecordFactory({
							...createdControlModel,
							...{ deviceId: createdControlModel.device.id },
						});

						await addRecord<IDeviceControlDatabaseRecord>(databaseRecordFactory(this.data[createdControlModel.id]), DB_TABLE_DEVICES_CONTROLS);

						this.meta[createdControlModel.id] = createdControlModel.type;

						return this.data[createdControlModel.id];
					} catch (e: any) {
						// Record could not be created on api, we have to remove it from database
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

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.device-controls.save.failed');
				}

				this.semaphore.updating.push(payload.id);

				const recordToSave = this.data[payload.id];

				const devicesStore = useDevices();

				const device = devicesStore.findById(recordToSave.device.id);

				if (device === null) {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

					throw new Error('devices-module.device-controls.get.failed');
				}

				try {
					const savedControl = await axios.post<IDeviceControlResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${recordToSave.device.id}/controls`,
						jsonApiFormatter.serialize({
							stuff: recordToSave,
						})
					);

					const savedControlModel = jsonApiFormatter.deserialize(savedControl.data) as IDeviceControlResponseModel;

					this.data[savedControlModel.id] = await storeRecordFactory({
						...savedControlModel,
						...{ deviceId: savedControlModel.device.id },
					});

					await addRecord<IDeviceControlDatabaseRecord>(databaseRecordFactory(this.data[savedControlModel.id]), DB_TABLE_DEVICES_CONTROLS);

					this.meta[savedControlModel.id] = savedControlModel.type;

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

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.device-controls.delete.failed');
				}

				this.semaphore.deleting.push(payload.id);

				const recordToDelete = this.data[payload.id];

				const devicesStore = useDevices();

				const device = devicesStore.findById(recordToDelete.device.id);

				if (device === null) {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);

					throw new Error('devices-module.device-controls.get.failed');
				}

				delete this.data[payload.id];

				await removeRecord(payload.id, DB_TABLE_DEVICES_CONTROLS);

				delete this.meta[payload.id];

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
			 * Transmit control command to server
			 *
			 * @param {IDeviceControlsTransmitCommandActionPayload} payload
			 */
			async transmitCommand(payload: IDeviceControlsTransmitCommandActionPayload): Promise<boolean> {
				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.device-controls.transmit.failed');
				}

				const control = this.data[payload.id];

				const devicesStore = useDevices();

				const device = devicesStore.findById(control.device.id);

				if (device === null) {
					throw new Error('devices-module.device-controls.transmit.failed');
				}

				const { call } = useWampV1Client<{ data: string }>();

				try {
					const response = await call('', {
						routing_key: ActionRoutes.DEVICE_CONTROL,
						source: control.type.source,
						data: {
							action: ExchangeCommand.SET,
							device: device.id,
							control: control.id,
							expected_value: payload.value,
						},
					});

					if (get(response.data, 'response') === 'accepted') {
						return true;
					}
				} catch (e) {
					throw new Error('devices-module.device-controls.transmit.failed');
				}

				throw new Error('devices-module.device-controls.transmit.failed');
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

				const body: DeviceControlDocument = JSON.parse(payload.data);

				const isValid = jsonSchemaValidator.compile<DeviceControlDocument>(exchangeDocumentSchema);

				try {
					if (!isValid(body)) {
						return false;
					}
				} catch {
					return false;
				}

				if (payload.routingKey === RoutingKeys.DEVICE_CONTROL_DOCUMENT_DELETED) {
					await removeRecord(body.id, DB_TABLE_DEVICES_CONTROLS);

					delete this.meta[body.id];

					if (this.data && body.id in this.data) {
						delete this.data[body.id];
					}
				} else {
					if (this.data && body.id in this.data) {
						const record = await storeRecordFactory({
							...this.data[body.id],
							...{
								name: body.name,
								deviceId: body.device,
							},
						});

						if (!isEqual(JSON.parse(JSON.stringify(this.data[body.id])), JSON.parse(JSON.stringify(record)))) {
							this.data[body.id] = record;

							await addRecord<IDeviceControlDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DEVICES_CONTROLS);

							this.meta[record.id] = record.type;
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
						}
					}
				}

				return true;
			},

			/**
			 * Insert data from SSR
			 *
			 * @param {IDeviceControlsInsertDataActionPayload} payload
			 */
			async insertData(payload: IDeviceControlsInsertDataActionPayload) {
				this.data = this.data ?? {};

				let documents: DeviceControlDocument[] = [];

				if (Array.isArray(payload.data)) {
					documents = payload.data;
				} else {
					documents = [payload.data];
				}

				const deviceIds = [];

				for (const doc of documents) {
					const isValid = jsonSchemaValidator.compile<DeviceControlDocument>(exchangeDocumentSchema);

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
								parent: 'device',
								entity: 'control',
							},
							name: doc.name,
							deviceId: doc.device,
						},
					});

					if (documents.length === 1) {
						this.data[doc.id] = record;
					}

					await addRecord<IDeviceControlDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DEVICES_CONTROLS);

					this.meta[record.id] = record.type;

					deviceIds.push(doc.device);
				}

				return true;
			},

			/**
			 * Load record from database
			 *
			 * @param {IDeviceControlsLoadRecordActionPayload} payload
			 */
			async loadRecord(payload: IDeviceControlsLoadRecordActionPayload): Promise<boolean> {
				const record = await getRecord<IDeviceControlDatabaseRecord>(payload.id, DB_TABLE_DEVICES_CONTROLS);

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
			 * @param {IDeviceControlsLoadAllRecordsActionPayload} payload
			 */
			async loadAllRecords(payload?: IDeviceControlsLoadAllRecordsActionPayload): Promise<boolean> {
				const records = await getAllRecords<IDeviceControlDatabaseRecord>(DB_TABLE_DEVICES_CONTROLS);

				this.data = this.data ?? {};

				for (const record of records) {
					if (payload?.device && payload?.device.id !== record?.device.id) {
						continue;
					}

					this.data[record.id] = await storeRecordFactory(record);
				}

				return true;
			},
		},
	}
);

export const registerDevicesControlsStore = (pinia: Pinia): Store => {
	return useDeviceControls(pinia);
};
