import { defineStore } from 'pinia';
import axios from 'axios';
import { Jsona } from 'jsona';
import Ajv from 'ajv/dist/2020';
import { v4 as uuid } from 'uuid';
import get from 'lodash/get';
import isEqual from 'lodash/isEqual';

import exchangeDocumentSchema from '@fastybird/metadata-library/resources/schemas/modules/devices-module/document.connector.property.json';
import {
	ConnectorPropertyDocument,
	DevicesModuleRoutes as RoutingKeys,
	ModulePrefix,
	PropertyCategory,
	PropertyType,
} from '@fastybird/metadata-library';

import { ApiError } from '@/errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '@/jsonapi';
import { useConnectors } from '@/models';
import { IConnector } from '@/models/types';

import {
	IConnectorPropertiesState,
	IConnectorPropertiesActions,
	IConnectorPropertiesGetters,
	IConnectorPropertiesAddActionPayload,
	IConnectorPropertiesEditActionPayload,
	IConnectorPropertiesFetchActionPayload,
	IConnectorPropertiesGetActionPayload,
	IConnectorPropertiesRemoveActionPayload,
	IConnectorPropertiesResponseJson,
	IConnectorPropertiesSaveActionPayload,
	IConnectorPropertiesSetActionPayload,
	IConnectorPropertiesSocketDataActionPayload,
	IConnectorPropertiesUnsetActionPayload,
	IConnectorProperty,
	IConnectorPropertyRecordFactoryPayload,
	IConnectorPropertyResponseJson,
	IConnectorPropertyResponseModel,
} from './types';

const jsonSchemaValidator = new Ajv();

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const recordFactory = async (data: IConnectorPropertyRecordFactoryPayload): Promise<IConnectorProperty> => {
	const connectorsStore = useConnectors();

	let connector = connectorsStore.findById(data.connectorId);

	if (connector === null) {
		if (!(await connectorsStore.get({ id: data.connectorId }))) {
			throw new Error("Connector for property couldn't be loaded from server");
		}

		connector = connectorsStore.findById(data.connectorId);

		if (connector === null) {
			throw new Error("Connector for property couldn't be loaded from store");
		}
	}

	return {
		id: get(data, 'id', uuid().toString()),
		type: { ...{ parent: 'connector', entity: 'property' }, ...data.type },

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

		parent: null,
		children: [],
	} as IConnectorProperty;
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

				firstLoad: [],

				data: {},
			};
		},

		getters: {
			firstLoadFinished: (state: IConnectorPropertiesState): ((connectorId: string) => boolean) => {
				return (connectorId) => state.firstLoad.includes(connectorId);
			},

			getting: (state: IConnectorPropertiesState): ((propertyId: string) => boolean) => {
				return (propertyId) => state.semaphore.fetching.item.includes(propertyId);
			},

			fetching: (state: IConnectorPropertiesState): ((connectorId: string | null) => boolean) => {
				return (connectorId) =>
					connectorId !== null ? state.semaphore.fetching.items.includes(connectorId) : state.semaphore.fetching.items.length > 0;
			},

			findById: (state: IConnectorPropertiesState): ((id: string) => IConnectorProperty | null) => {
				return (id) => {
					const property = Object.values(state.data).find((property) => property.id === id);

					return property ?? null;
				};
			},

			findByIdentifier: (state: IConnectorPropertiesState): ((connector: IConnector, identifier: string) => IConnectorProperty | null) => {
				return (connector: IConnector, identifier) => {
					const property = Object.values(state.data).find((property) => {
						return property.connector.id === connector.id && property.identifier.toLowerCase() === identifier.toLowerCase();
					});

					return property ?? null;
				};
			},

			findForConnector: (state: IConnectorPropertiesState): ((connectorId: string) => IConnectorProperty[]) => {
				return (connectorId: string): IConnectorProperty[] => {
					return Object.values(state.data).filter((property) => property.connector.id === connectorId);
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
			 * @param {IConnectorPropertiesUnsetActionPayload} payload
			 */
			unset(payload: IConnectorPropertiesUnsetActionPayload): void {
				if (typeof payload.connector !== 'undefined') {
					Object.keys(this.data).forEach((id) => {
						if (id in this.data && this.data[id].connector.id === payload.connector?.id) {
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

				this.semaphore.fetching.item.push(payload.id);

				try {
					const propertyResponse = await axios.get<IConnectorPropertyResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${payload.connector.id}/properties/${payload.id}`
					);

					const propertyResponseModel = jsonApiFormatter.deserialize(propertyResponse.data) as IConnectorPropertyResponseModel;

					this.data[propertyResponseModel.id] = await recordFactory({
						...propertyResponseModel,
						...{ connectorId: propertyResponseModel.connector.id, parentId: propertyResponseModel.parent?.id },
					});
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

				this.semaphore.fetching.items.push(payload.connector.id);

				try {
					const propertiesResponse = await axios.get<IConnectorPropertiesResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${payload.connector.id}/properties`
					);

					const propertiesResponseModel = jsonApiFormatter.deserialize(propertiesResponse.data) as IConnectorPropertyResponseModel[];

					for (const property of propertiesResponseModel) {
						this.data[property.id] = await recordFactory({
							...property,
							...{ connectorId: property.connector.id, parentId: property.parent?.id },
						});
					}

					this.firstLoad.push(payload.connector.id);
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
				const newProperty = await recordFactory({
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

				this.data[newProperty.id] = newProperty;

				if (newProperty.draft) {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newProperty.id);

					return newProperty;
				} else {
					try {
						const apiData: Partial<IConnectorProperty> = {
							id: newProperty.id,
							type: newProperty.type,
							identifier: newProperty.identifier,
							name: newProperty.name,
							connector: newProperty.connector,
							relationshipNames: ['connector'],
						};

						if (apiData?.type?.type === PropertyType.DYNAMIC && 'value' in apiData) {
							delete apiData.value;
						}

						const createdProperty = await axios.post<IConnectorPropertyResponseJson>(
							`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${payload.connector.id}/properties`,
							jsonApiFormatter.serialize({
								stuff: apiData,
							})
						);

						const createdPropertyModel = jsonApiFormatter.deserialize(createdProperty.data) as IConnectorPropertyResponseModel;

						this.data[createdPropertyModel.id] = await recordFactory({
							...createdPropertyModel,
							...{ connectorId: createdPropertyModel.connector.id, parentId: createdPropertyModel.parent?.id },
						});

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

				if (!Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.connector-properties.update.failed');
				}

				this.semaphore.updating.push(payload.id);

				// Get record stored in database
				const existingRecord = this.data[payload.id];
				// Update with new values
				const updatedRecord = { ...existingRecord, ...payload.data } as IConnectorProperty;

				this.data[payload.id] = updatedRecord;

				if (updatedRecord.draft) {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

					return this.data[payload.id];
				} else {
					try {
						const apiData: Partial<IConnectorProperty> = {
							id: updatedRecord.id,
							type: updatedRecord.type,
							identifier: updatedRecord.identifier,
							name: updatedRecord.name,
							connector: updatedRecord.connector,
							relationshipNames: ['connector'],
						};

						if (apiData?.type?.type === PropertyType.DYNAMIC && 'value' in apiData) {
							delete apiData.value;
						}

						const updatedProperty = await axios.patch<IConnectorPropertyResponseJson>(
							`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${updatedRecord.connector.id}/properties/${updatedRecord.id}`,
							jsonApiFormatter.serialize({
								stuff: apiData,
							})
						);

						const updatedPropertyModel = jsonApiFormatter.deserialize(updatedProperty.data) as IConnectorPropertyResponseModel;

						this.data[updatedPropertyModel.id] = await recordFactory({
							...updatedPropertyModel,
							...{ connectorId: updatedPropertyModel.connector.id, parentId: updatedPropertyModel.parent?.id },
						});

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
			 * Save draft record on server
			 *
			 * @param {IConnectorPropertiesSaveActionPayload} payload
			 */
			async save(payload: IConnectorPropertiesSaveActionPayload): Promise<IConnectorProperty> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('devices-module.connector-properties.save.inProgress');
				}

				if (!Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.connector-properties.save.failed');
				}

				this.semaphore.updating.push(payload.id);

				const recordToSave = this.data[payload.id];

				try {
					const apiData: Partial<IConnectorProperty> = {
						id: recordToSave.id,
						type: recordToSave.type,
						identifier: recordToSave.identifier,
						name: recordToSave.name,
						connector: recordToSave.connector,
						relationshipNames: ['connector'],
					};

					if (apiData?.type?.type === PropertyType.DYNAMIC && 'value' in apiData) {
						delete apiData.value;
					}

					const savedProperty = await axios.post<IConnectorPropertyResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${recordToSave.connector.id}/properties`,
						jsonApiFormatter.serialize({
							stuff: recordToSave,
						})
					);

					const savedPropertyModel = jsonApiFormatter.deserialize(savedProperty.data) as IConnectorPropertyResponseModel;

					this.data[savedPropertyModel.id] = await recordFactory({
						...savedPropertyModel,
						...{ connectorId: savedPropertyModel.connector.id, parentId: savedPropertyModel.parent?.id },
					});

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

				if (!Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.connector-properties.delete.failed');
				}

				this.semaphore.deleting.push(payload.id);

				const recordToDelete = this.data[payload.id];

				delete this.data[payload.id];

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
					if (body.id in this.data) {
						delete this.data[body.id];
					}
				} else {
					if (payload.routingKey === RoutingKeys.CONNECTOR_PROPERTY_DOCUMENT_UPDATED && this.semaphore.updating.includes(body.id)) {
						return true;
					}

					if (body.id in this.data) {
						const record = await recordFactory({
							...this.data[body.id],
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
						} else {
							try {
								await connectorsStore.get({ id: body.connector });
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
