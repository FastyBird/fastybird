import {
	ConnectorPropertyDocument,
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

import exchangeDocumentSchema from '../../../resources/schemas/document.connector.property.json';

import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { useConnectors } from '../../models';
import {
	IConnector,
	IConnectorPropertiesInsertDataActionPayload,
	IConnectorPropertiesLoadAllRecordsActionPayload,
	IConnectorPropertiesLoadRecordActionPayload,
	IConnectorPropertiesSetStateActionPayload,
	IConnectorPropertyDatabaseRecord,
	IConnectorPropertyMeta,
} from '../../models/types';
import { addRecord, getAllRecords, getRecord, removeRecord, DB_TABLE_CONNECTORS_PROPERTIES } from '../../utilities/database';

import {
	IConnectorPropertiesActions,
	IConnectorPropertiesAddActionPayload,
	IConnectorPropertiesEditActionPayload,
	IConnectorPropertiesFetchActionPayload,
	IConnectorPropertiesGetActionPayload,
	IConnectorPropertiesGetters,
	IConnectorPropertiesRemoveActionPayload,
	IConnectorPropertiesResponseJson,
	IConnectorPropertiesSaveActionPayload,
	IConnectorPropertiesSetActionPayload,
	IConnectorPropertiesSocketDataActionPayload,
	IConnectorPropertiesState,
	IConnectorPropertiesUnsetActionPayload,
	IConnectorProperty,
	IConnectorPropertyRecordFactoryPayload,
	IConnectorPropertyResponseJson,
	IConnectorPropertyResponseModel,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (data: IConnectorPropertyRecordFactoryPayload): Promise<IConnectorProperty> => {
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
			throw new Error("Connector for property couldn't be loaded from store");
		}

		if (!(await connectorsStore.get({ id: data.connectorId as string, refresh: false }))) {
			throw new Error("Connector for property couldn't be loaded from server");
		}

		connectorMeta = connectorsStore.findMeta(data.connectorId as string);

		if (connectorMeta === null) {
			throw new Error("Connector for property couldn't be loaded from store");
		}

		connector = {
			id: data.connectorId as string,
			type: connectorMeta,
		};
	}

	return {
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
		relationshipNames: ['connector'],

		connector: {
			id: connector.id,
			type: connector.type,
		},
	};
};

const databaseRecordFactory = (record: IConnectorProperty): IConnectorPropertyDatabaseRecord => {
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

		connector: {
			id: record.connector.id,
			type: {
				type: record.connector.type.type,
				source: record.connector.type.source,
				entity: record.connector.type.entity,
			},
		},
	};
};

export const useConnectorProperties = defineStore<string, IConnectorPropertiesState, IConnectorPropertiesGetters, IConnectorPropertiesActions>(
	'devices_module_connectors_properties',
	{
		state: (): IConnectorPropertiesState => {
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
			getting: (state: IConnectorPropertiesState): ((id: IConnectorProperty['id']) => boolean) => {
				return (id: IConnectorProperty['id']): boolean => state.semaphore.fetching.item.includes(id);
			},

			fetching: (state: IConnectorPropertiesState): ((connectorId: IConnector['id'] | null) => boolean) => {
				return (connectorId: IConnector['id'] | null): boolean =>
					connectorId !== null ? state.semaphore.fetching.items.includes(connectorId) : state.semaphore.fetching.items.length > 0;
			},

			findById: (state: IConnectorPropertiesState): ((id: IConnectorProperty['id']) => IConnectorProperty | null) => {
				return (id: IConnectorProperty['id']): IConnectorProperty | null => {
					const property: IConnectorProperty | undefined = Object.values(state.data ?? {}).find(
						(property: IConnectorProperty): boolean => property.id === id
					);

					return property ?? null;
				};
			},

			findByIdentifier: (
				state: IConnectorPropertiesState
			): ((connector: IConnector, identifier: IConnectorProperty['identifier']) => IConnectorProperty | null) => {
				return (connector: IConnector, identifier: IConnectorProperty['identifier']): IConnectorProperty | null => {
					const property: IConnectorProperty | undefined = Object.values(state.data ?? {}).find((property: IConnectorProperty): boolean => {
						return property.connector.id === connector.id && property.identifier.toLowerCase() === identifier.toLowerCase();
					});

					return property ?? null;
				};
			},

			findForConnector: (state: IConnectorPropertiesState): ((connectorId: IConnector['id']) => IConnectorProperty[]) => {
				return (connectorId: IConnector['id']): IConnectorProperty[] => {
					return Object.values(state.data ?? {}).filter((property: IConnectorProperty): boolean => property.connector.id === connectorId);
				};
			},

			findMeta: (state: IConnectorPropertiesState): ((id: IConnectorProperty['id']) => IConnectorPropertyMeta | null) => {
				return (id: IConnectorProperty['id']): IConnectorPropertyMeta | null => {
					return id in state.meta ? state.meta[id] : null;
				};
			},
		},

		actions: {
			/**
			 * Set record from via other store
			 *
			 * @param {IConnectorPropertiesSetActionPayload} payload
			 */
			async set(payload: IConnectorPropertiesSetActionPayload): Promise<IConnectorProperty> {
				if (this.data && payload.data.id && payload.data.id in this.data) {
					const record = await storeRecordFactory({ ...this.data[payload.data.id], ...payload.data });

					return (this.data[record.id] = record);
				}

				const record = await storeRecordFactory(payload.data);

				await addRecord<IConnectorPropertyDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CONNECTORS_PROPERTIES);

				this.meta[record.id] = record.type;

				this.data = this.data ?? {};
				return (this.data[record.id] = record);
			},

			/**
			 * Remove records for given relation or record by given identifier
			 *
			 * @param {IConnectorPropertiesUnsetActionPayload} payload
			 */
			async unset(payload: IConnectorPropertiesUnsetActionPayload): Promise<void> {
				if (!this.data) {
					return;
				}

				if (payload.connector !== undefined) {
					const items = this.findForConnector(payload.connector.id);

					for (const item of items) {
						if (item.id in (this.data ?? {})) {
							await removeRecord(item.id, DB_TABLE_CONNECTORS_PROPERTIES);

							delete this.meta[item.id];

							delete (this.data ?? {})[item.id];
						}
					}

					return;
				} else if (payload.id !== undefined) {
					await removeRecord(payload.id, DB_TABLE_CONNECTORS_PROPERTIES);

					delete this.meta[payload.id];

					delete this.data[payload.id];

					return;
				}

				throw new Error('You have to provide at least connector or property id');
			},

			/**
			 * Get one record from server
			 *
			 * @param {IConnectorPropertiesGetActionPayload} payload
			 */
			async get(payload: IConnectorPropertiesGetActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.item.includes(payload.id)) {
					return false;
				}

				const fromDatabase = await this.loadRecord({ id: payload.id });

				if (fromDatabase && payload.refresh === false) {
					return true;
				}

				this.semaphore.fetching.item.push(payload.id);

				try {
					const propertyResponse = await axios.get<IConnectorPropertyResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${payload.connector.id}/properties/${payload.id}`
					);

					const propertyResponseModel = jsonApiFormatter.deserialize(propertyResponse.data) as IConnectorPropertyResponseModel;

					this.data = this.data ?? {};
					this.data[propertyResponseModel.id] = await storeRecordFactory({
						...propertyResponseModel,
						...{ connectorId: propertyResponseModel.connector.id },
					});

					await addRecord<IConnectorPropertyDatabaseRecord>(
						databaseRecordFactory(this.data[propertyResponseModel.id]),
						DB_TABLE_CONNECTORS_PROPERTIES
					);

					this.meta[propertyResponseModel.id] = propertyResponseModel.type;
				} catch (e: any) {
					throw new ApiError('devices-module.connector-properties.get.failed', e, 'Fetching property failed.');
				} finally {
					this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
				}

				return true;
			},

			/**
			 * Fetch all records from server
			 *
			 * @param {IConnectorPropertiesFetchActionPayload} payload
			 */
			async fetch(payload: IConnectorPropertiesFetchActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.items.includes(payload.connector.id)) {
					return false;
				}

				const fromDatabase = await this.loadAllRecords({ connector: payload.connector });

				if (fromDatabase && payload?.refresh === false) {
					return true;
				}

				this.semaphore.fetching.items.push(payload.connector.id);

				try {
					const propertiesResponse = await axios.get<IConnectorPropertiesResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${payload.connector.id}/properties`
					);

					const propertiesResponseModel = jsonApiFormatter.deserialize(propertiesResponse.data) as IConnectorPropertyResponseModel[];

					for (const property of propertiesResponseModel) {
						this.data = this.data ?? {};
						this.data[property.id] = await storeRecordFactory({
							...property,
							...{ connectorId: property.connector.id },
						});

						await addRecord<IConnectorPropertyDatabaseRecord>(databaseRecordFactory(this.data[property.id]), DB_TABLE_CONNECTORS_PROPERTIES);

						this.meta[property.id] = property.type;
					}

					// Get all current IDs from IndexedDB
					const allRecords = await getAllRecords<IConnectorPropertyDatabaseRecord>(DB_TABLE_CONNECTORS_PROPERTIES);
					const indexedDbIds: string[] = allRecords.filter((record) => record.connector.id === payload.connector.id).map((record) => record.id);

					// Get the IDs from the latest changes
					const serverIds: string[] = Object.keys(this.data ?? {});

					// Find IDs that are in IndexedDB but not in the server response
					const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

					// Remove records that are no longer present on the server
					for (const id of idsToRemove) {
						await removeRecord(id, DB_TABLE_CONNECTORS_PROPERTIES);

						delete this.meta[id];
					}
				} catch (e: any) {
					throw new ApiError('devices-module.connector-properties.fetch.failed', e, 'Fetching properties failed.');
				} finally {
					this.semaphore.fetching.items = this.semaphore.fetching.items.filter((item) => item !== payload.connector.id);
				}

				return true;
			},

			/**
			 * Add new record
			 *
			 * @param {IConnectorPropertiesAddActionPayload} payload
			 */
			async add(payload: IConnectorPropertiesAddActionPayload): Promise<IConnectorProperty> {
				const newProperty = await storeRecordFactory({
					...{
						id: payload?.id,
						type: payload?.type,
						category: PropertyCategory.GENERIC,
						draft: payload?.draft,
						connectorId: payload.connector.id,
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
					const connectorsStore = useConnectors();

					const connector = connectorsStore.findById(payload.connector.id);

					if (connector === null) {
						this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newProperty.id);

						throw new Error('devices-module.connector-properties.create.failed');
					}

					try {
						const apiData: Partial<IConnectorProperty> = {
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
							connector: newProperty.connector,
							relationshipNames: ['connector'],
						};

						if (apiData?.type?.type === PropertyType.DYNAMIC) {
							delete apiData.value;
						}

						if (apiData?.type?.type === PropertyType.VARIABLE) {
							delete apiData.settable;
							delete apiData.queryable;
						}

						const createdProperty = await axios.post<IConnectorPropertyResponseJson>(
							`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${payload.connector.id}/properties`,
							jsonApiFormatter.serialize({
								stuff: apiData,
							})
						);

						const createdPropertyModel = jsonApiFormatter.deserialize(createdProperty.data) as IConnectorPropertyResponseModel;

						this.data[createdPropertyModel.id] = await storeRecordFactory({
							...createdPropertyModel,
							...{ connectorId: createdPropertyModel.connector.id },
						});

						await addRecord<IConnectorPropertyDatabaseRecord>(
							databaseRecordFactory(this.data[createdPropertyModel.id]),
							DB_TABLE_CONNECTORS_PROPERTIES
						);

						this.meta[createdPropertyModel.id] = createdPropertyModel.type;

						return this.data[createdPropertyModel.id];
					} catch (e: any) {
						// Transformer could not be created on api, we have to remove it from database
						delete this.data[newProperty.id];

						throw new ApiError('devices-module.connector-properties.create.failed', e, 'Create new property failed.');
					} finally {
						this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newProperty.id);
					}
				}
			},

			/**
			 * Edit existing record
			 *
			 * @param {IConnectorPropertiesEditActionPayload} payload
			 */
			async edit(payload: IConnectorPropertiesEditActionPayload): Promise<IConnectorProperty> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('devices-module.connector-properties.update.inProgress');
				}

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.connector-properties.update.failed');
				}

				this.semaphore.updating.push(payload.id);

				// Get record stored in database
				const existingRecord = this.data[payload.id];
				// Update with new values
				const updatedRecord = {
					...existingRecord,
					...payload.data,
				} as IConnectorProperty;

				this.data[payload.id] = updatedRecord;

				if (updatedRecord.draft) {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

					return this.data[payload.id];
				} else {
					const connectorsStore = useConnectors();

					const connector = connectorsStore.findById(updatedRecord.connector.id);

					if (connector === null) {
						this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

						throw new Error('devices-module.connector-properties.update.failed');
					}

					try {
						const apiData: Partial<IConnectorProperty> = {
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
							connector: updatedRecord.connector,
							relationshipNames: ['connector'],
						};

						if (apiData?.type?.type === PropertyType.DYNAMIC) {
							delete apiData.value;
						}

						if (apiData?.type?.type === PropertyType.VARIABLE) {
							delete apiData.settable;
							delete apiData.queryable;
						}

						const updatedProperty = await axios.patch<IConnectorPropertyResponseJson>(
							`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${updatedRecord.connector.id}/properties/${updatedRecord.id}`,
							jsonApiFormatter.serialize({
								stuff: apiData,
							})
						);

						const updatedPropertyModel = jsonApiFormatter.deserialize(updatedProperty.data) as IConnectorPropertyResponseModel;

						this.data[updatedPropertyModel.id] = await storeRecordFactory({
							...updatedPropertyModel,
							...{ connectorId: updatedPropertyModel.connector.id },
						});

						await addRecord<IConnectorPropertyDatabaseRecord>(
							databaseRecordFactory(this.data[updatedPropertyModel.id]),
							DB_TABLE_CONNECTORS_PROPERTIES
						);

						this.meta[updatedPropertyModel.id] = updatedPropertyModel.type;

						return this.data[updatedPropertyModel.id];
					} catch (e: any) {
						const connectorsStore = useConnectors();

						const connector = connectorsStore.findById(updatedRecord.connector.id);

						if (connector !== null) {
							// Updating entity on api failed, we need to refresh entity
							await this.get({ connector, id: payload.id });
						}

						throw new ApiError('devices-module.connector-properties.update.failed', e, 'Edit property failed.');
					} finally {
						this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
					}
				}
			},

			/**
			 * Save property state record
			 *
			 * @param {IConnectorPropertiesSetStateActionPayload} payload
			 */
			async setState(payload: IConnectorPropertiesSetStateActionPayload): Promise<IConnectorProperty> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('devices-module.connector-properties.update.inProgress');
				}

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.connector-properties.update.failed');
				}

				this.semaphore.updating.push(payload.id);

				// Get record stored in database
				const existingRecord = this.data[payload.id];
				// Update with new values
				this.data[payload.id] = {
					...existingRecord,
					...payload.data,
				} as IConnectorProperty;

				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

				return this.data[payload.id];
			},

			/**
			 * Save draft record on server
			 *
			 * @param {IConnectorPropertiesSaveActionPayload} payload
			 */
			async save(payload: IConnectorPropertiesSaveActionPayload): Promise<IConnectorProperty> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('devices-module.connector-properties.save.inProgress');
				}

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.connector-properties.save.failed');
				}

				this.semaphore.updating.push(payload.id);

				const recordToSave = this.data[payload.id];

				const connectorsStore = useConnectors();

				const connector = connectorsStore.findById(recordToSave.connector.id);

				if (connector === null) {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

					throw new Error('devices-module.connector-properties.save.failed');
				}

				try {
					const apiData: Partial<IConnectorProperty> = {
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
						connector: recordToSave.connector,
						relationshipNames: ['connector'],
					};

					if (apiData?.type?.type === PropertyType.DYNAMIC) {
						delete apiData.value;
					}

					if (apiData?.type?.type === PropertyType.VARIABLE) {
						delete apiData.settable;
						delete apiData.queryable;
					}

					const savedProperty = await axios.post<IConnectorPropertyResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${recordToSave.connector.id}/properties`,
						jsonApiFormatter.serialize({
							stuff: apiData,
						})
					);

					const savedPropertyModel = jsonApiFormatter.deserialize(savedProperty.data) as IConnectorPropertyResponseModel;

					this.data[savedPropertyModel.id] = await storeRecordFactory({
						...savedPropertyModel,
						...{ connectorId: savedPropertyModel.connector.id },
					});

					await addRecord<IConnectorPropertyDatabaseRecord>(databaseRecordFactory(this.data[savedPropertyModel.id]), DB_TABLE_CONNECTORS_PROPERTIES);

					this.meta[savedPropertyModel.id] = savedPropertyModel.type;

					return this.data[savedPropertyModel.id];
				} catch (e: any) {
					throw new ApiError('devices-module.connector-properties.save.failed', e, 'Save draft property failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}
			},

			/**
			 * Remove existing record from store and server
			 *
			 * @param {IConnectorPropertiesRemoveActionPayload} payload
			 */
			async remove(payload: IConnectorPropertiesRemoveActionPayload): Promise<boolean> {
				if (this.semaphore.deleting.includes(payload.id)) {
					throw new Error('devices-module.connector-properties.delete.inProgress');
				}

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.connector-properties.delete.failed');
				}

				this.semaphore.deleting.push(payload.id);

				const recordToDelete = this.data[payload.id];

				const connectorsStore = useConnectors();

				const connector = connectorsStore.findById(recordToDelete.connector.id);

				if (connector === null) {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);

					throw new Error('devices-module.connector-properties.delete.failed');
				}

				delete this.data[payload.id];

				await removeRecord(payload.id, DB_TABLE_CONNECTORS_PROPERTIES);

				delete this.meta[payload.id];

				if (recordToDelete.draft) {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				} else {
					try {
						await axios.delete(`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${recordToDelete.connector.id}/properties/${recordToDelete.id}`);
					} catch (e: any) {
						const connectorsStore = useConnectors();

						const connector = connectorsStore.findById(recordToDelete.connector.id);

						if (connector !== null) {
							// Deleting entity on api failed, we need to refresh entity
							await this.get({ connector, id: payload.id });
						}

						throw new ApiError('devices-module.connector-properties.delete.failed', e, 'Delete property failed.');
					} finally {
						this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
					}
				}

				return true;
			},

			/**
			 * Receive data from sockets
			 *
			 * @param {IConnectorPropertiesSocketDataActionPayload} payload
			 */
			async socketData(payload: IConnectorPropertiesSocketDataActionPayload): Promise<boolean> {
				if (
					![
						RoutingKeys.CONNECTOR_PROPERTY_DOCUMENT_REPORTED,
						RoutingKeys.CONNECTOR_PROPERTY_DOCUMENT_CREATED,
						RoutingKeys.CONNECTOR_PROPERTY_DOCUMENT_UPDATED,
						RoutingKeys.CONNECTOR_PROPERTY_DOCUMENT_DELETED,
					].includes(payload.routingKey as RoutingKeys)
				) {
					return false;
				}

				const body: ConnectorPropertyDocument = JSON.parse(payload.data);

				const isValid = jsonSchemaValidator.compile<ConnectorPropertyDocument>(exchangeDocumentSchema);

				try {
					if (!isValid(body)) {
						return false;
					}
				} catch {
					return false;
				}

				if (payload.routingKey === RoutingKeys.CONNECTOR_PROPERTY_DOCUMENT_DELETED) {
					await removeRecord(body.id, DB_TABLE_CONNECTORS_PROPERTIES);

					delete this.meta[body.id];

					if (this.data && body.id in this.data) {
						delete this.data[body.id];
					}
				} else {
					if (payload.routingKey === RoutingKeys.CONNECTOR_PROPERTY_DOCUMENT_UPDATED && this.semaphore.updating.includes(body.id)) {
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
								connectorId: body.connector,
							},
						});

						if (!isEqual(JSON.parse(JSON.stringify(this.data[body.id])), JSON.parse(JSON.stringify(record)))) {
							this.data[body.id] = record;

							await addRecord<IConnectorPropertyDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CONNECTORS_PROPERTIES);

							this.meta[record.id] = record.type;
						}
					} else {
						const connectorsStore = useConnectors();

						const connector = connectorsStore.findById(body.connector);

						if (connector !== null) {
							try {
								await this.get({
									connector,
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
			 * @param {IConnectorPropertiesInsertDataActionPayload} payload
			 */
			async insertData(payload: IConnectorPropertiesInsertDataActionPayload) {
				this.data = this.data ?? {};

				let documents: ConnectorPropertyDocument[] = [];

				if (Array.isArray(payload.data)) {
					documents = payload.data;
				} else {
					documents = [payload.data];
				}

				const connectorIds = [];

				for (const doc of documents) {
					const isValid = jsonSchemaValidator.compile<ConnectorPropertyDocument>(exchangeDocumentSchema);

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
								parent: 'connector',
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
							connectorId: doc.connector,
						},
					});

					if (documents.length === 1) {
						this.data[doc.id] = record;
					}

					await addRecord<IConnectorPropertyDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CONNECTORS_PROPERTIES);

					this.meta[record.id] = record.type;

					connectorIds.push(doc.connector);
				}

				return true;
			},

			/**
			 * Load record from database
			 *
			 * @param {IConnectorPropertiesLoadRecordActionPayload} payload
			 */
			async loadRecord(payload: IConnectorPropertiesLoadRecordActionPayload): Promise<boolean> {
				const record = await getRecord<IConnectorPropertyDatabaseRecord>(payload.id, DB_TABLE_CONNECTORS_PROPERTIES);

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
			 * @param {IConnectorPropertiesLoadAllRecordsActionPayload} payload
			 */
			async loadAllRecords(payload?: IConnectorPropertiesLoadAllRecordsActionPayload): Promise<boolean> {
				const records = await getAllRecords<IConnectorPropertyDatabaseRecord>(DB_TABLE_CONNECTORS_PROPERTIES);

				this.data = this.data ?? {};

				for (const record of records) {
					if (payload?.connector && payload?.connector.id !== record?.connector.id) {
						continue;
					}

					this.data[record.id] = await storeRecordFactory(record);
				}

				return true;
			},
		},
	}
);
