import {
	ConnectorCategory,
	ConnectorDocument,
	DevicePropertyIdentifier,
	DevicesModuleRoutes as RoutingKeys,
	ModulePrefix,
} from '@fastybird/metadata-library';
import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios from 'axios';
import { Jsona } from 'jsona';
import get from 'lodash.get';
import isEqual from 'lodash.isequal';
import { defineStore, Pinia, Store } from 'pinia';
import { v4 as uuid } from 'uuid';

import exchangeDocumentSchema from '../../../resources/schemas/document.connector.json';

import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { useConnectorControls, useConnectorProperties } from '../../models';
import {
	IConnectorControlResponseModel,
	IConnectorDatabaseRecord,
	IConnectorMeta,
	IConnectorProperty,
	IConnectorPropertyResponseModel,
	IConnectorsInsertDataActionPayload,
	IConnectorsLoadRecordActionPayload,
	IPlainRelation,
} from '../../models/types';
import { addRecord, getAllRecords, getRecord, removeRecord, DB_TABLE_CONNECTORS } from '../../utilities/database';

import {
	IConnector,
	IConnectorRecordFactoryPayload,
	IConnectorResponseJson,
	IConnectorResponseModel,
	IConnectorsActions,
	IConnectorsAddActionPayload,
	IConnectorsEditActionPayload,
	IConnectorsFetchActionPayload,
	IConnectorsGetActionPayload,
	IConnectorsGetters,
	IConnectorsRemoveActionPayload,
	IConnectorsResponseJson,
	IConnectorsSaveActionPayload,
	IConnectorsSetActionPayload,
	IConnectorsSocketDataActionPayload,
	IConnectorsState,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = (data: IConnectorRecordFactoryPayload): IConnector => {
	const record: IConnector = {
		id: get(data, 'id', uuid().toString()),
		type: data.type,

		draft: get(data, 'draft', false),

		category: data.category,
		identifier: data.identifier,
		name: get(data, 'name', null),
		comment: get(data, 'comment', null),
		enabled: get(data, 'enabled', false),

		relationshipNames: ['properties', 'controls', 'devices'],

		properties: [],
		controls: [],
		devices: [],

		owner: get(data, 'owner', null),

		get isEnabled(): boolean {
			return this.enabled;
		},

		get stateProperty(): IConnectorProperty | null {
			const connectorPropertiesStore = useConnectorProperties();

			const stateProperty = connectorPropertiesStore
				.findForConnector(this.id)
				.find((property) => property.identifier === DevicePropertyIdentifier.STATE);

			return stateProperty ?? null;
		},

		get hasComment(): boolean {
			return this.comment !== null && this.comment !== '';
		},
	};

	record.relationshipNames.forEach((relationName) => {
		get(data, relationName, []).forEach((relation: any): void => {
			if (
				relationName === 'properties' ||
				relationName === 'controls' ||
				(relationName === 'devices' && get(relation, 'id', null) !== null && get(relation, 'type', null) !== null)
			) {
				(record[relationName] as IPlainRelation[]).push({
					id: get(relation, 'id', null),
					type: get(relation, 'type', null),
				});
			}
		});
	});

	return record;
};

const databaseRecordFactory = (record: IConnector): IConnectorDatabaseRecord => {
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
		enabled: record.enabled,

		relationshipNames: record.relationshipNames.map((name) => name),

		devices: record.devices.map((device) => ({
			id: device.id,
			type: { type: device.type.type, source: device.type.source, entity: device.type.entity },
		})),

		controls: record.controls.map((control) => ({
			id: control.id,
			type: { type: control.type.type, source: control.type.source, entity: control.type.entity, parent: control.type.parent },
		})),
		properties: record.properties.map((property) => ({
			id: property.id,
			type: { type: property.type.type, source: property.type.source, entity: property.type.entity, parent: property.type.parent },
		})),

		owner: record.owner,
	};
};

const addPropertiesRelations = async (connector: IConnector, properties: (IConnectorPropertyResponseModel | IPlainRelation)[]): Promise<void> => {
	const propertiesStore = useConnectorProperties();

	for (const property of properties) {
		if ('identifier' in property) {
			await propertiesStore.set({
				data: {
					...property,
					...{
						connectorId: connector.id,
					},
				},
			});
		}
	}
};

const addControlsRelations = async (connector: IConnector, controls: (IConnectorControlResponseModel | IPlainRelation)[]): Promise<void> => {
	const controlsStore = useConnectorControls();

	for (const control of controls) {
		if ('identifier' in control) {
			await controlsStore.set({
				data: {
					...control,
					...{
						connectorId: connector.id,
					},
				},
			});
		}
	}
};

export const useConnectors = defineStore<string, IConnectorsState, IConnectorsGetters, IConnectorsActions>('devices_module_connectors', {
	state: (): IConnectorsState => {
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

			data: undefined,
			meta: {},
		};
	},

	getters: {
		firstLoadFinished: (state: IConnectorsState): (() => boolean) => {
			return (): boolean => state.firstLoad;
		},

		getting: (state: IConnectorsState): ((id: IConnector['id']) => boolean) => {
			return (id: IConnector['id']): boolean => state.semaphore.fetching.item.includes(id);
		},

		fetching: (state: IConnectorsState): (() => boolean) => {
			return (): boolean => state.semaphore.fetching.items;
		},

		findById: (state: IConnectorsState): ((id: IConnector['id']) => IConnector | null) => {
			return (id: IConnector['id']): IConnector | null => {
				return id in (state.data ?? {}) ? (state.data ?? {})[id] : null;
			};
		},

		findAll: (state: IConnectorsState): (() => IConnector[]) => {
			return (): IConnector[] => {
				return Object.values(state.data ?? {});
			};
		},

		findMeta: (state: IConnectorsState): ((id: IConnector['id']) => IConnectorMeta | null) => {
			return (id: IConnector['id']): IConnectorMeta | null => {
				return id in state.meta ? state.meta[id] : null;
			};
		},
	},

	actions: {
		/**
		 * Set record from via other store
		 *
		 * @param {IConnectorsSetActionPayload} payload
		 */
		async set(payload: IConnectorsSetActionPayload): Promise<IConnector> {
			const record = storeRecordFactory(payload.data);

			if ('properties' in payload.data && Array.isArray(payload.data.properties)) {
				await addPropertiesRelations(record, payload.data.properties);
			}

			if ('controls' in payload.data && Array.isArray(payload.data.controls)) {
				await addControlsRelations(record, payload.data.controls);
			}

			await addRecord<IConnectorDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CONNECTORS);

			this.meta[record.id] = record.type;

			this.data = this.data ?? {};
			return (this.data[record.id] = record);
		},

		/**
		 * Get one record from server
		 *
		 * @param {IConnectorsGetActionPayload} payload
		 */
		async get(payload: IConnectorsGetActionPayload): Promise<boolean> {
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
				const connectorResponse = await axios.get<IConnectorResponseJson>(`/${ModulePrefix.DEVICES}/v1/connectors/${payload.id}`);

				const connectorResponseModel = jsonApiFormatter.deserialize(connectorResponse.data) as IConnectorResponseModel;

				this.data = this.data ?? {};
				this.data[connectorResponseModel.id] = storeRecordFactory(connectorResponseModel);

				await addRecord<IConnectorDatabaseRecord>(databaseRecordFactory(this.data[connectorResponseModel.id]), DB_TABLE_CONNECTORS);

				this.meta[connectorResponseModel.id] = connectorResponseModel.type;
			} catch (e: any) {
				throw new ApiError('devices-module.connectors.get.failed', e, 'Fetching connector failed.');
			} finally {
				if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
					this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
				}
			}

			const promises: Promise<boolean>[] = [];

			const propertiesStore = useConnectorProperties();
			promises.push(propertiesStore.fetch({ connector: this.data[payload.id] }));

			const controlsStore = useConnectorControls();
			promises.push(controlsStore.fetch({ connector: this.data[payload.id] }));

			Promise.all(promises).catch((e: any): void => {
				throw new ApiError('devices-module.connectors.get.failed', e, 'Fetching connector failed.');
			});

			return true;
		},

		/**
		 * Fetch all records from server
		 *
		 * @param {IConnectorsFetchActionPayload} payload
		 */
		async fetch(payload?: IConnectorsFetchActionPayload): Promise<boolean> {
			if (this.semaphore.fetching.items) {
				return false;
			}

			const fromDatabase = await this.loadAllRecords();

			if (fromDatabase && payload?.refresh === false) {
				return true;
			}

			if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
				this.semaphore.fetching.items = true;
			}

			this.firstLoad = false;

			try {
				const connectorsResponse = await axios.get<IConnectorsResponseJson>(`/${ModulePrefix.DEVICES}/v1/connectors`);

				const connectorsResponseModel = jsonApiFormatter.deserialize(connectorsResponse.data) as IConnectorResponseModel[];

				for (const connector of connectorsResponseModel) {
					this.data = this.data ?? {};
					this.data[connector.id] = storeRecordFactory(connector);

					await addRecord<IConnectorDatabaseRecord>(databaseRecordFactory(this.data[connector.id]), DB_TABLE_CONNECTORS);

					this.meta[connector.id] = connector.type;
				}

				this.firstLoad = true;

				// Get all current IDs from IndexedDB
				const allRecords = await getAllRecords<IConnectorDatabaseRecord>(DB_TABLE_CONNECTORS);
				const indexedDbIds: string[] = allRecords.map((record) => record.id);

				// Get the IDs from the latest changes
				const serverIds: string[] = Object.keys(this.data ?? {});

				// Find IDs that are in IndexedDB but not in the server response
				const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

				// Remove records that are no longer present on the server
				for (const id of idsToRemove) {
					await removeRecord(id, DB_TABLE_CONNECTORS);

					delete this.meta[id];
				}
			} catch (e: any) {
				throw new ApiError('devices-module.connectors.fetch.failed', e, 'Fetching connectors failed.');
			} finally {
				if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
					this.semaphore.fetching.items = false;
				}
			}

			const promises: Promise<boolean>[] = [];

			const propertiesStore = useConnectorProperties();
			const controlsStore = useConnectorControls();

			for (const connector of Object.values(this.data ?? {})) {
				promises.push(propertiesStore.fetch({ connector }));
				promises.push(controlsStore.fetch({ connector }));
			}

			Promise.all(promises).catch((e: any): void => {
				throw new ApiError('devices-module.connectors.fetch.failed', e, 'Fetching connectors failed.');
			});

			return true;
		},

		/**
		 * Add new record
		 *
		 * @param {IConnectorsAddActionPayload} payload
		 */
		async add(payload: IConnectorsAddActionPayload): Promise<IConnector> {
			const newConnector = storeRecordFactory({
				...payload.data,
				...{ id: payload?.id, type: payload?.type, category: ConnectorCategory.GENERIC, draft: payload?.draft },
			});

			this.semaphore.creating.push(newConnector.id);

			this.data = this.data ?? {};
			this.data[newConnector.id] = newConnector;

			if (newConnector.draft) {
				this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newConnector.id);

				return newConnector;
			} else {
				try {
					const createdConnector = await axios.post<IConnectorResponseJson>(
						`/${ModulePrefix.DEVICES}/v1/connectors`,
						jsonApiFormatter.serialize({
							stuff: newConnector,
						})
					);

					const createdConnectorModel = jsonApiFormatter.deserialize(createdConnector.data) as IConnectorResponseModel;

					this.data[createdConnectorModel.id] = storeRecordFactory(createdConnectorModel);

					await addRecord<IConnectorDatabaseRecord>(databaseRecordFactory(this.data[createdConnectorModel.id]), DB_TABLE_CONNECTORS);

					this.meta[createdConnectorModel.id] = createdConnectorModel.type;
				} catch (e: any) {
					// Record could not be created on api, we have to remove it from database
					delete this.data[newConnector.id];

					throw new ApiError('devices-module.connectors.create.failed', e, 'Create new connector failed.');
				} finally {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newConnector.id);
				}

				const promises: Promise<boolean>[] = [];

				const propertiesStore = useConnectorProperties();
				promises.push(propertiesStore.fetch({ connector: this.data[newConnector.id] }));

				const controlsStore = useConnectorControls();
				promises.push(controlsStore.fetch({ connector: this.data[newConnector.id] }));

				Promise.all(promises).catch((e: any): void => {
					throw new ApiError('devices-module.connectors.create.failed', e, 'Create new connector failed.');
				});

				return this.data[newConnector.id];
			}
		},

		/**
		 * Edit existing record
		 *
		 * @param {IConnectorsEditActionPayload} payload
		 */
		async edit(payload: IConnectorsEditActionPayload): Promise<IConnector> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('devices-module.connectors.update.inProgress');
			}

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				throw new Error('devices-module.connectors.update.failed');
			}

			this.semaphore.updating.push(payload.id);

			// Get record stored in database
			const existingRecord = this.data[payload.id];
			// Update with new values
			const updatedRecord = { ...existingRecord, ...payload.data } as IConnector;

			this.data[payload.id] = updatedRecord;

			if (updatedRecord.draft) {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

				return this.data[payload.id];
			} else {
				try {
					const updatedConnector = await axios.patch<IConnectorResponseJson>(
						`/${ModulePrefix.DEVICES}/v1/connectors/${payload.id}`,
						jsonApiFormatter.serialize({
							stuff: updatedRecord,
						})
					);

					const updatedConnectorModel = jsonApiFormatter.deserialize(updatedConnector.data) as IConnectorResponseModel;

					this.data[updatedConnectorModel.id] = storeRecordFactory(updatedConnectorModel);

					await addRecord<IConnectorDatabaseRecord>(databaseRecordFactory(this.data[updatedConnectorModel.id]), DB_TABLE_CONNECTORS);

					this.meta[updatedConnectorModel.id] = updatedConnectorModel.type;
				} catch (e: any) {
					// Updating record on api failed, we need to refresh record
					await this.get({ id: payload.id });

					throw new ApiError('devices-module.connectors.update.failed', e, 'Edit connector failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}

				const promises: Promise<boolean>[] = [];

				const propertiesStore = useConnectorProperties();
				promises.push(propertiesStore.fetch({ connector: this.data[payload.id] }));

				const controlsStore = useConnectorControls();
				promises.push(controlsStore.fetch({ connector: this.data[payload.id] }));

				Promise.all(promises).catch((e: any): void => {
					throw new ApiError('devices-module.connectors.update.failed', e, 'Edit connector failed.');
				});

				return this.data[payload.id];
			}
		},

		/**
		 * Save draft record on server
		 *
		 * @param {IConnectorsSaveActionPayload} payload
		 */
		async save(payload: IConnectorsSaveActionPayload): Promise<IConnector> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('devices-module.connectors.save.inProgress');
			}

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				throw new Error('devices-module.connectors.save.failed');
			}

			this.semaphore.updating.push(payload.id);

			const recordToSave = this.data[payload.id];

			try {
				const savedConnector = await axios.post<IConnectorResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/connectors`,
					jsonApiFormatter.serialize({
						stuff: recordToSave,
					})
				);

				const savedConnectorModel = jsonApiFormatter.deserialize(savedConnector.data) as IConnectorResponseModel;

				this.data[savedConnectorModel.id] = storeRecordFactory(savedConnectorModel);

				await addRecord<IConnectorDatabaseRecord>(databaseRecordFactory(this.data[savedConnectorModel.id]), DB_TABLE_CONNECTORS);

				this.meta[savedConnectorModel.id] = savedConnectorModel.type;
			} catch (e: any) {
				throw new ApiError('devices-module.connectors.save.failed', e, 'Save draft connector failed.');
			} finally {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
			}

			const promises: Promise<boolean>[] = [];

			const propertiesStore = useConnectorProperties();
			promises.push(propertiesStore.fetch({ connector: this.data[payload.id] }));

			const controlsStore = useConnectorControls();
			promises.push(controlsStore.fetch({ connector: this.data[payload.id] }));

			Promise.all(promises).catch((e: any): void => {
				throw new ApiError('devices-module.connectors.save.failed', e, 'Save draft channel failed.');
			});

			return this.data[payload.id];
		},

		/**
		 * Remove existing record from store and server
		 *
		 * @param {IConnectorsRemoveActionPayload} payload
		 */
		async remove(payload: IConnectorsRemoveActionPayload): Promise<boolean> {
			if (this.semaphore.deleting.includes(payload.id)) {
				throw new Error('devices-module.connectors.delete.inProgress');
			}

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				return true;
			}

			const propertiesStore = useConnectorProperties();
			const controlsStore = useConnectorControls();

			this.semaphore.deleting.push(payload.id);

			const recordToDelete = this.data[payload.id];

			delete this.data[payload.id];

			await removeRecord(payload.id, DB_TABLE_CONNECTORS);

			delete this.meta[payload.id];

			if (recordToDelete.draft) {
				this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);

				propertiesStore.unset({ connector: recordToDelete });
				controlsStore.unset({ connector: recordToDelete });
			} else {
				try {
					await axios.delete(`/${ModulePrefix.DEVICES}/v1/connectors/${payload.id}`);

					propertiesStore.unset({ connector: recordToDelete });
					controlsStore.unset({ connector: recordToDelete });
				} catch (e: any) {
					// Deleting record on api failed, we need to refresh record
					await this.get({ id: payload.id });

					throw new ApiError('devices-module.connectors.delete.failed', e, 'Delete connector failed.');
				} finally {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				}
			}

			return true;
		},

		/**
		 * Receive data from sockets
		 *
		 * @param {IConnectorsSocketDataActionPayload} payload
		 */
		async socketData(payload: IConnectorsSocketDataActionPayload): Promise<boolean> {
			if (
				![
					RoutingKeys.CONNECTOR_DOCUMENT_REPORTED,
					RoutingKeys.CONNECTOR_DOCUMENT_CREATED,
					RoutingKeys.CONNECTOR_DOCUMENT_UPDATED,
					RoutingKeys.CONNECTOR_DOCUMENT_DELETED,
				].includes(payload.routingKey as RoutingKeys)
			) {
				return false;
			}

			const body: ConnectorDocument = JSON.parse(payload.data);

			const isValid = jsonSchemaValidator.compile<ConnectorDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(body)) {
					return false;
				}
			} catch {
				return false;
			}

			if (payload.routingKey === RoutingKeys.CONNECTOR_DOCUMENT_DELETED) {
				await removeRecord(body.id, DB_TABLE_CONNECTORS);

				delete this.meta[body.id];

				if (this.data && body.id in this.data) {
					const recordToDelete = this.data[body.id];

					delete this.data[body.id];

					const propertiesStore = useConnectorProperties();
					const controlsStore = useConnectorControls();

					propertiesStore.unset({ connector: recordToDelete });
					controlsStore.unset({ connector: recordToDelete });
				}
			} else {
				if (payload.routingKey === RoutingKeys.CONNECTOR_DOCUMENT_UPDATED && this.semaphore.updating.includes(body.id)) {
					return true;
				}

				if (this.data && body.id in this.data) {
					const record = storeRecordFactory({
						...this.data[body.id],
						...{
							category: body.category,
							name: body.name,
							comment: body.comment,
							enabled: body.enabled,
							owner: body.owner,
						},
					});

					if (!isEqual(JSON.parse(JSON.stringify(this.data[body.id])), JSON.parse(JSON.stringify(record)))) {
						this.data[body.id] = record;

						await addRecord<IConnectorDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CONNECTORS);

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
		 * @param {IConnectorsInsertDataActionPayload} payload
		 */
		async insertData(payload: IConnectorsInsertDataActionPayload): Promise<boolean> {
			this.data = this.data ?? {};

			let documents: ConnectorDocument[] = [];

			if (Array.isArray(payload.data)) {
				documents = payload.data;
			} else {
				documents = [payload.data];
			}

			for (const doc of documents) {
				const isValid = jsonSchemaValidator.compile<ConnectorDocument>(exchangeDocumentSchema);

				try {
					if (!isValid(doc)) {
						return false;
					}
				} catch {
					return false;
				}

				const record = storeRecordFactory({
					...this.data[doc.id],
					...{
						id: doc.id,
						type: {
							type: doc.type,
							source: doc.source,
							entity: 'connector',
						},
						category: doc.category,
						identifier: doc.identifier,
						name: doc.name,
						comment: doc.comment,
						enabled: doc.enabled,
						owner: doc.owner,
					},
				});

				if (documents.length === 1) {
					this.data[doc.id] = record;
				}

				await addRecord<IConnectorDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CONNECTORS);

				this.meta[record.id] = record.type;
			}

			return true;
		},

		/**
		 * Load record from database
		 *
		 * @param {IConnectorsLoadRecordActionPayload} payload
		 */
		async loadRecord(payload: IConnectorsLoadRecordActionPayload): Promise<boolean> {
			const record = await getRecord<IConnectorDatabaseRecord>(payload.id, DB_TABLE_CONNECTORS);

			if (record) {
				this.data = this.data ?? {};
				this.data[payload.id] = storeRecordFactory(record);

				return true;
			}

			return false;
		},

		/**
		 * Load records from database
		 */
		async loadAllRecords(): Promise<boolean> {
			const records = await getAllRecords<IConnectorDatabaseRecord>(DB_TABLE_CONNECTORS);

			this.data = this.data ?? {};

			for (const record of records) {
				this.data[record.id] = storeRecordFactory(record);
			}

			return true;
		},
	},
});

export const registerConnectorsStore = (pinia: Pinia): Store => {
	return useConnectors(pinia);
};
