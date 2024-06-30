import {
	DevicePropertyDocument,
	DevicesModuleRoutes as RoutingKeys,
	ModulePrefix,
	PropertyCategory,
	PropertyType,
} from '@fastybird/metadata-library';
import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios from 'axios';
import { Jsona } from 'jsona';
import get from 'lodash.get';
import isEqual from 'lodash.isequal';
import { defineStore } from 'pinia';
import { v4 as uuid } from 'uuid';

import exchangeDocumentSchema from '../../../resources/schemas/document.device.property.json';

import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { useDevices } from '../../models';
import {
	IDevice,
	IDevicePropertiesInsertDataActionPayload,
	IDevicePropertiesLoadAllRecordsActionPayload,
	IDevicePropertiesLoadRecordActionPayload,
	IDevicePropertiesSetStateActionPayload,
	IDevicePropertyDatabaseRecord,
	IDevicePropertyMeta,
	IPlainRelation,
} from '../../models/types';
import { addRecord, getAllRecords, getRecord, removeRecord, DB_TABLE_DEVICES_PROPERTIES } from '../../utilities/database';

import {
	IDevicePropertiesActions,
	IDevicePropertiesAddActionPayload,
	IDevicePropertiesEditActionPayload,
	IDevicePropertiesFetchActionPayload,
	IDevicePropertiesGetActionPayload,
	IDevicePropertiesGetters,
	IDevicePropertiesRemoveActionPayload,
	IDevicePropertiesResponseJson,
	IDevicePropertiesSaveActionPayload,
	IDevicePropertiesSetActionPayload,
	IDevicePropertiesSocketDataActionPayload,
	IDevicePropertiesState,
	IDevicePropertiesUnsetActionPayload,
	IDeviceProperty,
	IDevicePropertyRecordFactoryPayload,
	IDevicePropertyResponseJson,
	IDevicePropertyResponseModel,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (data: IDevicePropertyRecordFactoryPayload): Promise<IDeviceProperty> => {
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
			throw new Error("Device for property couldn't be loaded from store");
		}

		if (!(await devicesStore.get({ id: data.deviceId as string, refresh: false }))) {
			throw new Error("Device for property couldn't be loaded from server");
		}

		deviceMeta = devicesStore.findMeta(data.deviceId as string);

		if (deviceMeta === null) {
			throw new Error("Device for property couldn't be loaded from store");
		}

		device = {
			id: data.deviceId as string,
			type: deviceMeta,
		};
	}

	const record: IDeviceProperty = {
		id: get(data, 'id', uuid().toString()),
		type: data.type,

		draft: get(data, 'draft', false),

		category: data.category,
		identifier: data.identifier,
		name: get(data, 'name', null),
		settable: get(data, 'settable', false),
		queryable: get(data, 'queryable', false),
		dataType: data.dataType,
		unit: get(data, 'unit', null),
		format: get(data, 'format', null),
		invalid: get(data, 'invalid', null),
		scale: get(data, 'scale', null),
		step: get(data, 'step', null),
		default: get(data, 'default', null),
		valueTransformer: get(data, 'valueTransformer', null),

		value: get(data, 'value', null),
		actualValue: get(data, 'actualValue', null),
		expectedValue: get(data, 'expectedValue', null),
		pending: get(data, 'pending', false),
		isValid: get(data, 'isValid', false),
		command: get(data, 'command', null),
		lastResult: get(data, 'lastResult', null),
		backupValue: get(data, 'backup', null),

		// Relations
		relationshipNames: ['device', 'parent', 'children'],

		device: {
			id: device.id,
			type: device.type,
		},

		parent: null,
		children: [],
	};

	record.relationshipNames.forEach((relationName) => {
		if (relationName === 'children') {
			get(data, relationName, []).forEach((relation: any): void => {
				if (get(relation, 'id', null) !== null && get(relation, 'type', null) !== null) {
					(record[relationName] as IPlainRelation[]).push({
						id: get(relation, 'id', null),
						type: get(relation, 'type', null),
					});
				}
			});
		} else if (relationName === 'parent') {
			const parentId = get(data, `${relationName}.id`, null);
			const parentType = get(data, `${relationName}.type`, null);

			if (parentId !== null && parentType !== null) {
				(record[relationName] as IPlainRelation) = {
					id: parentId,
					type: parentType,
				};
			}
		}
	});

	return record;
};

const databaseRecordFactory = (record: IDeviceProperty): IDevicePropertyDatabaseRecord => {
	return {
		id: record.id,
		type: {
			type: record.type.type,
			source: record.type.source,
			entity: record.type.entity,
			parent: record.type.parent,
		},

		category: record.category,
		identifier: record.identifier,
		name: record.name,
		settable: record.settable,
		queryable: record.queryable,
		dataType: record.dataType,
		unit: record.unit,
		format: record.format,
		invalid: record.invalid,
		scale: record.scale,
		step: record.step,
		valueTransformer: record.valueTransformer,
		default: record.default,

		// Static property
		value: record.value,

		relationshipNames: record.relationshipNames.map((name) => name),

		parent: record.parent
			? {
					id: record.device.id,
					type: {
						type: record.device.type.type,
						source: record.device.type.source,
						entity: record.device.type.entity,
						parent: record.device.type.parent,
					},
				}
			: null,
		children: record.children.map((children) => ({
			id: children.id,
			type: { type: children.type.type, source: children.type.source, entity: children.type.entity, parent: children.type.parent },
		})),

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

export const useDeviceProperties = defineStore<string, IDevicePropertiesState, IDevicePropertiesGetters, IDevicePropertiesActions>(
	'devices_module_devices_properties',
	{
		state: (): IDevicePropertiesState => {
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
			getting: (state: IDevicePropertiesState): ((id: IDeviceProperty['id']) => boolean) => {
				return (id: IDeviceProperty['id']): boolean => state.semaphore.fetching.item.includes(id);
			},

			fetching: (state: IDevicePropertiesState): ((deviceId: IDevice['id'] | null) => boolean) => {
				return (deviceId: IDevice['id'] | null): boolean =>
					deviceId !== null ? state.semaphore.fetching.items.includes(deviceId) : state.semaphore.fetching.items.length > 0;
			},

			findById: (state: IDevicePropertiesState): ((id: IDeviceProperty['id']) => IDeviceProperty | null) => {
				return (id: IDeviceProperty['id']): IDeviceProperty | null => {
					const property: IDeviceProperty | undefined = Object.values(state.data ?? {}).find(
						(property: IDeviceProperty): boolean => property.id === id
					);

					return property ?? null;
				};
			},

			findByIdentifier: (state: IDevicePropertiesState): ((device: IDevice, identifier: IDeviceProperty['identifier']) => IDeviceProperty | null) => {
				return (device: IDevice, identifier: IDeviceProperty['identifier']): IDeviceProperty | null => {
					const property: IDeviceProperty | undefined = Object.values(state.data ?? {}).find((property: IDeviceProperty): boolean => {
						return property.device.id === device.id && property.identifier.toLowerCase() === identifier.toLowerCase();
					});

					return property ?? null;
				};
			},

			findForDevice: (state: IDevicePropertiesState): ((deviceId: IDevice['id']) => IDeviceProperty[]) => {
				return (deviceId: IDevice['id']): IDeviceProperty[] => {
					return Object.values(state.data ?? {}).filter((property: IDeviceProperty): boolean => property.device.id === deviceId);
				};
			},

			findMeta: (state: IDevicePropertiesState): ((id: IDeviceProperty['id']) => IDevicePropertyMeta | null) => {
				return (id: IDeviceProperty['id']): IDevicePropertyMeta | null => {
					return id in state.meta ? state.meta[id] : null;
				};
			},
		},

		actions: {
			/**
			 * Set record from via other store
			 *
			 * @param {IDevicePropertiesSetActionPayload} payload
			 */
			async set(payload: IDevicePropertiesSetActionPayload): Promise<IDeviceProperty> {
				if (this.data && payload.data.id && payload.data.id in this.data) {
					const record = await storeRecordFactory({ ...this.data[payload.data.id], ...payload.data });

					return (this.data[record.id] = record);
				}

				const record = await storeRecordFactory(payload.data);

				await addRecord<IDevicePropertyDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DEVICES_PROPERTIES);

				this.meta[record.id] = record.type;

				this.data = this.data ?? {};
				return (this.data[record.id] = record);
			},

			/**
			 * Remove records for given relation or record by given identifier
			 *
			 * @param {IDevicePropertiesUnsetActionPayload} payload
			 */
			async unset(payload: IDevicePropertiesUnsetActionPayload): Promise<void> {
				if (!this.data) {
					return;
				}

				if (payload.device !== undefined) {
					const items = this.findForDevice(payload.device.id);

					for (const item of items) {
						if (item.id in (this.data ?? {})) {
							await removeRecord(item.id, DB_TABLE_DEVICES_PROPERTIES);

							delete this.meta[item.id];

							delete (this.data ?? {})[item.id];
						}
					}

					return;
				} else if (payload.id !== undefined) {
					await removeRecord(payload.id, DB_TABLE_DEVICES_PROPERTIES);

					delete this.meta[payload.id];

					delete this.data[payload.id];

					return;
				}

				throw new Error('You have to provide at least device or property id');
			},

			/**
			 * Get one record from server
			 *
			 * @param {IDevicePropertiesGetActionPayload} payload
			 */
			async get(payload: IDevicePropertiesGetActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.item.includes(payload.id)) {
					return false;
				}

				const fromDatabase = await this.loadRecord({ id: payload.id });

				if (fromDatabase && payload.refresh === false) {
					return true;
				}

				this.semaphore.fetching.item.push(payload.id);

				try {
					const propertyResponse = await axios.get<IDevicePropertyResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/properties/${payload.id}`
					);

					const propertyResponseModel = jsonApiFormatter.deserialize(propertyResponse.data) as IDevicePropertyResponseModel;

					this.data = this.data ?? {};
					this.data[propertyResponseModel.id] = await storeRecordFactory({
						...propertyResponseModel,
						...{ deviceId: propertyResponseModel.device.id, parentId: propertyResponseModel.parent?.id },
					});

					await addRecord<IDevicePropertyDatabaseRecord>(databaseRecordFactory(this.data[propertyResponseModel.id]), DB_TABLE_DEVICES_PROPERTIES);

					this.meta[propertyResponseModel.id] = propertyResponseModel.type;
				} catch (e: any) {
					throw new ApiError('devices-module.device-properties.get.failed', e, 'Fetching property failed.');
				} finally {
					this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
				}

				return true;
			},

			/**
			 * Fetch all records from server
			 *
			 * @param {IDevicePropertiesFetchActionPayload} payload
			 */
			async fetch(payload: IDevicePropertiesFetchActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.items.includes(payload.device.id)) {
					return false;
				}

				const fromDatabase = await this.loadAllRecords({ device: payload.device });

				if (fromDatabase && payload?.refresh === false) {
					return true;
				}

				this.semaphore.fetching.items.push(payload.device.id);

				try {
					const propertiesResponse = await axios.get<IDevicePropertiesResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/properties`
					);

					const propertiesResponseModel = jsonApiFormatter.deserialize(propertiesResponse.data) as IDevicePropertyResponseModel[];

					for (const property of propertiesResponseModel) {
						this.data = this.data ?? {};
						this.data[property.id] = await storeRecordFactory({
							...property,
							...{ deviceId: property.device.id, parentId: property.parent?.id },
						});

						await addRecord<IDevicePropertyDatabaseRecord>(databaseRecordFactory(this.data[property.id]), DB_TABLE_DEVICES_PROPERTIES);

						this.meta[property.id] = property.type;
					}

					// Get all current IDs from IndexedDB
					const allRecords = await getAllRecords<IDevicePropertyDatabaseRecord>(DB_TABLE_DEVICES_PROPERTIES);
					const indexedDbIds: string[] = allRecords.filter((record) => record.device.id === payload.device.id).map((record) => record.id);

					// Get the IDs from the latest changes
					const serverIds: string[] = Object.keys(this.data ?? {});

					// Find IDs that are in IndexedDB but not in the server response
					const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

					// Remove records that are no longer present on the server
					for (const id of idsToRemove) {
						await removeRecord(id, DB_TABLE_DEVICES_PROPERTIES);

						delete this.meta[id];
					}
				} catch (e: any) {
					throw new ApiError('devices-module.device-properties.fetch.failed', e, 'Fetching properties failed.');
				} finally {
					this.semaphore.fetching.items = this.semaphore.fetching.items.filter((item) => item !== payload.device.id);
				}

				return true;
			},

			/**
			 * Add new record
			 *
			 * @param {IDevicePropertiesAddActionPayload} payload
			 */
			async add(payload: IDevicePropertiesAddActionPayload): Promise<IDeviceProperty> {
				const newProperty = await storeRecordFactory({
					...{
						id: payload?.id,
						type: payload?.type,
						category: PropertyCategory.GENERIC,
						draft: payload?.draft,
						deviceId: payload.device.id,
					},
					...payload.data,
				});

				this.semaphore.creating.push(newProperty.id);

				this.data = this.data ?? {};
				this.data[newProperty.id] = newProperty;

				if (newProperty.draft) {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newProperty.id);

					return newProperty;
				} else {
					const devicesStore = useDevices();

					const device = devicesStore.findById(payload.device.id);

					if (device === null) {
						this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newProperty.id);

						throw new Error('devices-module.device-properties.create.failed');
					}

					try {
						const apiData: Partial<IDeviceProperty> =
							newProperty.parent !== null
								? {
										id: newProperty.id,
										type: newProperty.type,
										identifier: newProperty.identifier,
										name: newProperty.name,
										value: newProperty.value,
										device: newProperty.device,
										parent: newProperty.parent,
										relationshipNames: ['device', 'parent'],
									}
								: {
										id: newProperty.id,
										type: newProperty.type,
										identifier: newProperty.identifier,
										name: newProperty.name,
										value: newProperty.value,
										settable: newProperty.settable,
										queryable: newProperty.queryable,
										dataType: newProperty.dataType,
										unit: newProperty.unit,
										invalid: newProperty.invalid,
										scale: newProperty.scale,
										step: newProperty.step,
										format: newProperty.format,
										default: newProperty.default,
										valueTransformer: newProperty.valueTransformer,
										device: newProperty.device,
										relationshipNames: ['device'],
									};

						if (apiData?.type?.type === PropertyType.DYNAMIC) {
							delete apiData.value;
						}

						if (apiData?.type?.type === PropertyType.VARIABLE) {
							delete apiData.settable;
							delete apiData.queryable;
						}

						const createdProperty = await axios.post<IDevicePropertyResponseJson>(
							`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/properties`,
							jsonApiFormatter.serialize({
								stuff: apiData,
							})
						);

						const createdPropertyModel = jsonApiFormatter.deserialize(createdProperty.data) as IDevicePropertyResponseModel;

						this.data[createdPropertyModel.id] = await storeRecordFactory({
							...createdPropertyModel,
							...{ deviceId: createdPropertyModel.device.id, parentId: createdPropertyModel.parent?.id },
						});

						await addRecord<IDevicePropertyDatabaseRecord>(databaseRecordFactory(this.data[createdPropertyModel.id]), DB_TABLE_DEVICES_PROPERTIES);

						this.meta[createdPropertyModel.id] = createdPropertyModel.type;

						return this.data[createdPropertyModel.id];
					} catch (e: any) {
						// Transformer could not be created on api, we have to remove it from database
						delete this.data[newProperty.id];

						throw new ApiError('devices-module.device-properties.create.failed', e, 'Create new property failed.');
					} finally {
						this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newProperty.id);
					}
				}
			},

			/**
			 * Edit existing record
			 *
			 * @param {IDevicePropertiesEditActionPayload} payload
			 */
			async edit(payload: IDevicePropertiesEditActionPayload): Promise<IDeviceProperty> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('devices-module.device-properties.update.inProgress');
				}

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.device-properties.update.failed');
				}

				this.semaphore.updating.push(payload.id);

				// Get record stored in database
				const existingRecord = this.data[payload.id];
				// Update with new values
				const updatedRecord = {
					...existingRecord,
					...payload.data,
					...{ parent: payload.parent ? { id: payload.parent.id, type: payload.parent.type } : existingRecord.parent },
				} as IDeviceProperty;

				this.data[payload.id] = updatedRecord;

				if (updatedRecord.draft) {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

					return this.data[payload.id];
				} else {
					const devicesStore = useDevices();

					const device = devicesStore.findById(updatedRecord.device.id);

					if (device === null) {
						this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

						throw new Error('devices-module.device-properties.update.failed');
					}

					try {
						const apiData: Partial<IDeviceProperty> =
							updatedRecord.parent !== null
								? {
										id: updatedRecord.id,
										type: updatedRecord.type,
										identifier: updatedRecord.identifier,
										name: updatedRecord.name,
										value: updatedRecord.value,
										device: updatedRecord.device,
										parent: updatedRecord.parent,
										relationshipNames: ['device', 'parent'],
									}
								: {
										id: updatedRecord.id,
										type: updatedRecord.type,
										identifier: updatedRecord.identifier,
										name: updatedRecord.name,
										value: updatedRecord.value,
										settable: updatedRecord.settable,
										queryable: updatedRecord.queryable,
										dataType: updatedRecord.dataType,
										unit: updatedRecord.unit,
										invalid: updatedRecord.invalid,
										scale: updatedRecord.scale,
										step: updatedRecord.step,
										format: updatedRecord.format,
										default: updatedRecord.default,
										valueTransformer: updatedRecord.valueTransformer,
										device: updatedRecord.device,
										relationshipNames: ['device'],
									};

						if (apiData?.type?.type === PropertyType.DYNAMIC) {
							delete apiData.value;
						}

						if (apiData?.type?.type === PropertyType.VARIABLE) {
							delete apiData.settable;
							delete apiData.queryable;
						}

						const updatedProperty = await axios.patch<IDevicePropertyResponseJson>(
							`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${updatedRecord.device.id}/properties/${updatedRecord.id}`,
							jsonApiFormatter.serialize({
								stuff: apiData,
							})
						);

						const updatedPropertyModel = jsonApiFormatter.deserialize(updatedProperty.data) as IDevicePropertyResponseModel;

						this.data[updatedPropertyModel.id] = await storeRecordFactory({
							...updatedPropertyModel,
							...{ deviceId: updatedPropertyModel.device.id, parentId: updatedPropertyModel.parent?.id },
						});

						await addRecord<IDevicePropertyDatabaseRecord>(databaseRecordFactory(this.data[updatedPropertyModel.id]), DB_TABLE_DEVICES_PROPERTIES);

						this.meta[updatedPropertyModel.id] = updatedPropertyModel.type;

						return this.data[updatedPropertyModel.id];
					} catch (e: any) {
						const devicesStore = useDevices();

						const device = devicesStore.findById(updatedRecord.device.id);

						if (device !== null) {
							// Updating entity on api failed, we need to refresh entity
							await this.get({ device, id: payload.id });
						}

						throw new ApiError('devices-module.device-properties.update.failed', e, 'Edit property failed.');
					} finally {
						this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
					}
				}
			},

			/**
			 * Save property state record
			 *
			 * @param {IDevicePropertiesSetStateActionPayload} payload
			 */
			async setState(payload: IDevicePropertiesSetStateActionPayload): Promise<IDeviceProperty> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('devices-module.device-properties.update.inProgress');
				}

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.device-properties.update.failed');
				}

				this.semaphore.updating.push(payload.id);

				// Get record stored in database
				const existingRecord = this.data[payload.id];
				// Update with new values
				this.data[payload.id] = {
					...existingRecord,
					...payload.data,
					...{ parent: payload.parent ? { id: payload.parent.id, type: payload.parent.type } : existingRecord.parent },
				} as IDeviceProperty;

				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

				return this.data[payload.id];
			},

			/**
			 * Save draft record on server
			 *
			 * @param {IDevicePropertiesSaveActionPayload} payload
			 */
			async save(payload: IDevicePropertiesSaveActionPayload): Promise<IDeviceProperty> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('devices-module.device-properties.save.inProgress');
				}

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.device-properties.save.failed');
				}

				this.semaphore.updating.push(payload.id);

				const recordToSave = this.data[payload.id];

				const devicesStore = useDevices();

				const device = devicesStore.findById(recordToSave.device.id);

				if (device === null) {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

					throw new Error('devices-module.device-properties.save.failed');
				}

				try {
					const apiData: Partial<IDeviceProperty> =
						recordToSave.parent !== null
							? {
									id: recordToSave.id,
									type: recordToSave.type,
									identifier: recordToSave.identifier,
									name: recordToSave.name,
									value: recordToSave.value,
									device: recordToSave.device,
									parent: recordToSave.parent,
									relationshipNames: ['device', 'parent'],
								}
							: {
									id: recordToSave.id,
									type: recordToSave.type,
									identifier: recordToSave.identifier,
									name: recordToSave.name,
									value: recordToSave.value,
									settable: recordToSave.settable,
									queryable: recordToSave.queryable,
									dataType: recordToSave.dataType,
									unit: recordToSave.unit,
									invalid: recordToSave.invalid,
									scale: recordToSave.scale,
									step: recordToSave.step,
									format: recordToSave.format,
									default: recordToSave.default,
									valueTransformer: recordToSave.valueTransformer,
									device: recordToSave.device,
									relationshipNames: ['device'],
								};

					if (apiData?.type?.type === PropertyType.DYNAMIC) {
						delete apiData.value;
					}

					if (apiData?.type?.type === PropertyType.VARIABLE) {
						delete apiData.settable;
						delete apiData.queryable;
					}

					const savedProperty = await axios.post<IDevicePropertyResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${recordToSave.device.id}/properties`,
						jsonApiFormatter.serialize({
							stuff: apiData,
						})
					);

					const savedPropertyModel = jsonApiFormatter.deserialize(savedProperty.data) as IDevicePropertyResponseModel;

					this.data[savedPropertyModel.id] = await storeRecordFactory({
						...savedPropertyModel,
						...{ deviceId: savedPropertyModel.device.id, parentId: savedPropertyModel.parent?.id },
					});

					await addRecord<IDevicePropertyDatabaseRecord>(databaseRecordFactory(this.data[savedPropertyModel.id]), DB_TABLE_DEVICES_PROPERTIES);

					this.meta[savedPropertyModel.id] = savedPropertyModel.type;

					return this.data[savedPropertyModel.id];
				} catch (e: any) {
					throw new ApiError('devices-module.device-properties.save.failed', e, 'Save draft property failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}
			},

			/**
			 * Remove existing record from store and server
			 *
			 * @param {IDevicePropertiesRemoveActionPayload} payload
			 */
			async remove(payload: IDevicePropertiesRemoveActionPayload): Promise<boolean> {
				if (this.semaphore.deleting.includes(payload.id)) {
					throw new Error('devices-module.device-properties.delete.inProgress');
				}

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.device-properties.delete.failed');
				}

				this.semaphore.deleting.push(payload.id);

				const recordToDelete = this.data[payload.id];

				const devicesStore = useDevices();

				const device = devicesStore.findById(recordToDelete.device.id);

				if (device === null) {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);

					throw new Error('devices-module.device-properties.delete.failed');
				}

				delete this.data[payload.id];

				await removeRecord(payload.id, DB_TABLE_DEVICES_PROPERTIES);

				delete this.meta[payload.id];

				if (recordToDelete.draft) {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				} else {
					try {
						await axios.delete(`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${recordToDelete.device.id}/properties/${recordToDelete.id}`);
					} catch (e: any) {
						const devicesStore = useDevices();

						const device = devicesStore.findById(recordToDelete.device.id);

						if (device !== null) {
							// Deleting entity on api failed, we need to refresh entity
							await this.get({ device, id: payload.id });
						}

						throw new ApiError('devices-module.device-properties.delete.failed', e, 'Delete property failed.');
					} finally {
						this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
					}
				}

				return true;
			},

			/**
			 * Receive data from sockets
			 *
			 * @param {IDevicePropertiesSocketDataActionPayload} payload
			 */
			async socketData(payload: IDevicePropertiesSocketDataActionPayload): Promise<boolean> {
				if (
					![
						RoutingKeys.DEVICE_PROPERTY_DOCUMENT_REPORTED,
						RoutingKeys.DEVICE_PROPERTY_DOCUMENT_CREATED,
						RoutingKeys.DEVICE_PROPERTY_DOCUMENT_UPDATED,
						RoutingKeys.DEVICE_PROPERTY_DOCUMENT_DELETED,
					].includes(payload.routingKey as RoutingKeys)
				) {
					return false;
				}

				const body: DevicePropertyDocument = JSON.parse(payload.data);

				const isValid = jsonSchemaValidator.compile<DevicePropertyDocument>(exchangeDocumentSchema);

				try {
					if (!isValid(body)) {
						return false;
					}
				} catch {
					return false;
				}

				if (payload.routingKey === RoutingKeys.DEVICE_PROPERTY_DOCUMENT_DELETED) {
					await removeRecord(body.id, DB_TABLE_DEVICES_PROPERTIES);

					delete this.meta[body.id];

					if (this.data && body.id in this.data) {
						delete this.data[body.id];
					}
				} else {
					if (payload.routingKey === RoutingKeys.DEVICE_PROPERTY_DOCUMENT_UPDATED && this.semaphore.updating.includes(body.id)) {
						return true;
					}

					if (this.data && body.id in this.data) {
						const record = await storeRecordFactory({
							...JSON.parse(JSON.stringify(this.data[body.id])),
							...{
								category: body.category,
								name: body.name,
								settable: body.settable,
								queryable: body.queryable,
								dataType: body.data_type,
								unit: body.unit,
								format: body.format,
								invalid: body.invalid,
								scale: body.scale,
								step: body.step,
								default: body.default,
								valueTransformer: body.value_transformer,
								actualValue: body.actual_value,
								expectedValue: body.expected_value,
								value: body.value,
								pending: body.pending,
								isValid: body.is_valid,
								deviceId: body.device,
							},
						});

						if (!isEqual(JSON.parse(JSON.stringify(this.data[body.id])), JSON.parse(JSON.stringify(record)))) {
							this.data[body.id] = record;

							await addRecord<IDevicePropertyDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DEVICES_PROPERTIES);

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
			 * @param {IDevicePropertiesInsertDataActionPayload} payload
			 */
			async insertData(payload: IDevicePropertiesInsertDataActionPayload) {
				this.data = this.data ?? {};

				let documents: DevicePropertyDocument[] = [];

				if (Array.isArray(payload.data)) {
					documents = payload.data;
				} else {
					documents = [payload.data];
				}

				const deviceIds = [];

				for (const doc of documents) {
					const isValid = jsonSchemaValidator.compile<DevicePropertyDocument>(exchangeDocumentSchema);

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
								entity: 'property',
							},
							category: doc.category,
							identifier: doc.identifier,
							name: doc.name,
							settable: doc.settable,
							queryable: doc.queryable,
							dataType: doc.data_type,
							unit: doc.unit,
							format: doc.format,
							invalid: doc.invalid,
							scale: doc.scale,
							step: doc.step,
							valueTransformer: doc.value_transformer,
							default: doc.default,
							value: doc.value,
							deviceId: doc.device,
							parentId: doc.parent,
						},
					});

					if (documents.length === 1) {
						this.data[doc.id] = record;
					}

					await addRecord<IDevicePropertyDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DEVICES_PROPERTIES);

					this.meta[record.id] = record.type;

					deviceIds.push(doc.device);
				}

				return true;
			},

			/**
			 * Load record from database
			 *
			 * @param {IDevicePropertiesLoadRecordActionPayload} payload
			 */
			async loadRecord(payload: IDevicePropertiesLoadRecordActionPayload): Promise<boolean> {
				const record = await getRecord<IDevicePropertyDatabaseRecord>(payload.id, DB_TABLE_DEVICES_PROPERTIES);

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
			 * @param {IDevicePropertiesLoadAllRecordsActionPayload} payload
			 */
			async loadAllRecords(payload?: IDevicePropertiesLoadAllRecordsActionPayload): Promise<boolean> {
				const records = await getAllRecords<IDevicePropertyDatabaseRecord>(DB_TABLE_DEVICES_PROPERTIES);

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
