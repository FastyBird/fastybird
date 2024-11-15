import { defineStore, Pinia, Store } from 'pinia';
import axios, { AxiosError } from 'axios';
import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import { Jsona } from 'jsona';
import { v4 as uuid } from 'uuid';
import get from 'lodash.get';
import isEqual from 'lodash.isequal';

import {
	ActionRoutes,
	ExchangeCommand,
	ConnectorControlDocument,
	DevicesModuleRoutes as RoutingKeys,
	ModulePrefix,
} from '@fastybird/metadata-library';
import { useWampV1Client } from '@fastybird/vue-wamp-v1';

import exchangeDocumentSchema from '../../../resources/schemas/document.connector.control.json';

import { connectorsStoreKey } from '../../configuration';
import { storesManager } from '../../entry';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { IConnector } from '../connectors/types';
import { addRecord, getAllRecords, getRecord, removeRecord, DB_TABLE_CONNECTORS_CONTROLS } from '../../utilities';

import {
	IConnectorControl,
	IConnectorControlDatabaseRecord,
	IConnectorControlMeta,
	IConnectorControlRecordFactoryPayload,
	IConnectorControlResponseJson,
	IConnectorControlResponseModel,
	IConnectorControlsActions,
	IConnectorControlsAddActionPayload,
	IConnectorControlsFetchActionPayload,
	IConnectorControlsGetActionPayload,
	IConnectorControlsGetters,
	IConnectorControlsInsertDataActionPayload,
	IConnectorControlsLoadAllRecordsActionPayload,
	IConnectorControlsLoadRecordActionPayload,
	IConnectorControlsRemoveActionPayload,
	IConnectorControlsResponseJson,
	IConnectorControlsSaveActionPayload,
	IConnectorControlsSetActionPayload,
	IConnectorControlsSocketDataActionPayload,
	IConnectorControlsState,
	IConnectorControlsTransmitCommandActionPayload,
	IConnectorControlsUnsetActionPayload,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (data: IConnectorControlRecordFactoryPayload): Promise<IConnectorControl> => {
	const connectorsStore = storesManager.getStore(connectorsStoreKey);

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
			throw new Error("Connector for control couldn't be loaded from store");
		}

		if (!(await connectorsStore.get({ id: data.connectorId as string, refresh: false }))) {
			throw new Error("Connector for control couldn't be loaded from server");
		}

		connectorMeta = connectorsStore.findMeta(data.connectorId as string);

		if (connectorMeta === null) {
			throw new Error("Connector for control couldn't be loaded from store");
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

		name: data.name,

		// Relations
		relationshipNames: ['connector'],

		connector: {
			id: connector.id,
			type: connector.type,
		},
	} as IConnectorControl;
};

const databaseRecordFactory = (record: IConnectorControl): IConnectorControlDatabaseRecord => {
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

export const useConnectorControls = defineStore<string, IConnectorControlsState, IConnectorControlsGetters, IConnectorControlsActions>(
	'devices_module_connectors_controls',
	{
		state: (): IConnectorControlsState => {
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
			firstLoadFinished: (state: IConnectorControlsState): ((connectorId: IConnector['id']) => boolean) => {
				return (connectorId: IConnector['id']): boolean => state.firstLoad.includes(connectorId);
			},

			getting: (state: IConnectorControlsState): ((id: IConnectorControl['id']) => boolean) => {
				return (id: IConnectorControl['id']): boolean => state.semaphore.fetching.item.includes(id);
			},

			fetching: (state: IConnectorControlsState): ((connectorId: IConnector['id'] | null) => boolean) => {
				return (connectorId: IConnector['id'] | null): boolean =>
					connectorId !== null ? state.semaphore.fetching.items.includes(connectorId) : state.semaphore.fetching.items.length > 0;
			},

			findById: (state: IConnectorControlsState): ((id: IConnectorControl['id']) => IConnectorControl | null) => {
				return (id: IConnectorControl['id']): IConnectorControl | null => {
					const control: IConnectorControl | undefined = Object.values(state.data ?? {}).find(
						(control: IConnectorControl): boolean => control.id === id
					);

					return control ?? null;
				};
			},

			findByName: (state: IConnectorControlsState): ((connector: IConnector, name: IConnectorControl['name']) => IConnectorControl | null) => {
				return (connector: IConnector, name: IConnectorControl['name']): IConnectorControl | null => {
					const control: IConnectorControl | undefined = Object.values(state.data ?? {}).find((control: IConnectorControl): boolean => {
						return control.connector.id === connector.id && control.name.toLowerCase() === name.toLowerCase();
					});

					return control ?? null;
				};
			},

			findForConnector: (state: IConnectorControlsState): ((connectorId: IConnector['id']) => IConnectorControl[]) => {
				return (connectorId: IConnector['id']): IConnectorControl[] => {
					return Object.values(state.data ?? {}).filter((control: IConnectorControl): boolean => control.connector.id === connectorId);
				};
			},

			findMeta: (state: IConnectorControlsState): ((id: IConnectorControl['id']) => IConnectorControlMeta | null) => {
				return (id: IConnectorControl['id']): IConnectorControlMeta | null => {
					return id in state.meta ? state.meta[id] : null;
				};
			},
		},

		actions: {
			/**
			 * Set record from via other store
			 *
			 * @param {IConnectorControlsSetActionPayload} payload
			 */
			async set(payload: IConnectorControlsSetActionPayload): Promise<IConnectorControl> {
				if (this.data && payload.data.id && payload.data.id in this.data) {
					const record = await storeRecordFactory({ ...this.data[payload.data.id], ...payload.data });

					return (this.data[record.id] = record);
				}

				const record = await storeRecordFactory(payload.data);

				await addRecord<IConnectorControlDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CONNECTORS_CONTROLS);

				this.meta[record.id] = record.type;

				this.data = this.data ?? {};
				return (this.data[record.id] = record);
			},

			/**
			 * Remove records for given relation or record by given identifier
			 *
			 * @param {IConnectorControlsUnsetActionPayload} payload
			 */
			async unset(payload: IConnectorControlsUnsetActionPayload): Promise<void> {
				if (!this.data) {
					return;
				}

				if (payload.connector !== undefined) {
					const items = this.findForConnector(payload.connector.id);

					for (const item of items) {
						if (item.id in (this.data ?? {})) {
							await removeRecord(item.id, DB_TABLE_CONNECTORS_CONTROLS);

							delete this.meta[item.id];

							delete (this.data ?? {})[item.id];
						}
					}

					return;
				} else if (payload.id !== undefined) {
					await removeRecord(payload.id, DB_TABLE_CONNECTORS_CONTROLS);

					delete this.meta[payload.id];

					delete this.data[payload.id];

					return;
				}

				throw new Error('You have to provide at least connector or control id');
			},

			/**
			 * Get one record from server
			 *
			 * @param {IConnectorControlsGetActionPayload} payload
			 */
			async get(payload: IConnectorControlsGetActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.item.includes(payload.id)) {
					return false;
				}

				const fromDatabase = await this.loadRecord({ id: payload.id });

				if (fromDatabase && payload.refresh === false) {
					return true;
				}

				this.semaphore.fetching.item.push(payload.id);

				try {
					const controlResponse = await axios.get<IConnectorControlResponseJson>(
						`/${ModulePrefix.DEVICES}/v1/connectors/${payload.connector.id}/controls/${payload.id}`
					);

					const controlResponseModel = jsonApiFormatter.deserialize(controlResponse.data) as IConnectorControlResponseModel;

					this.data = this.data ?? {};
					this.data[controlResponseModel.id] = await storeRecordFactory({
						...controlResponseModel,
						...{ connectorId: controlResponseModel.connector.id },
					});

					await addRecord<IConnectorControlDatabaseRecord>(databaseRecordFactory(this.data[controlResponseModel.id]), DB_TABLE_CONNECTORS_CONTROLS);

					this.meta[controlResponseModel.id] = controlResponseModel.type;
				} catch (e: any) {
					if (e instanceof AxiosError && e.status === 404) {
						this.unset({
							id: payload.id,
						});

						return true;
					}

					throw new ApiError('devices-module.connector-controls.get.failed', e, 'Fetching control failed.');
				} finally {
					this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
				}

				return true;
			},

			/**
			 * Fetch all records from server
			 *
			 * @param {IConnectorControlsFetchActionPayload} payload
			 */
			async fetch(payload: IConnectorControlsFetchActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.items.includes(payload.connector.id)) {
					return false;
				}

				const fromDatabase = await this.loadAllRecords({ connector: payload.connector });

				if (fromDatabase && payload?.refresh === false) {
					return true;
				}

				if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
					this.semaphore.fetching.items.push(payload.connector.id);
				}

				this.firstLoad = this.firstLoad.filter((item) => item !== payload.connector.id);
				this.firstLoad = [...new Set(this.firstLoad)];

				try {
					const controlsResponse = await axios.get<IConnectorControlsResponseJson>(
						`/${ModulePrefix.DEVICES}/v1/connectors/${payload.connector.id}/controls`
					);

					const controlsResponseModel = jsonApiFormatter.deserialize(controlsResponse.data) as IConnectorControlResponseModel[];

					for (const control of controlsResponseModel) {
						this.data = this.data ?? {};
						this.data[control.id] = await storeRecordFactory({
							...control,
							...{ connectorId: control.connector.id },
						});

						await addRecord<IConnectorControlDatabaseRecord>(databaseRecordFactory(this.data[control.id]), DB_TABLE_CONNECTORS_CONTROLS);

						this.meta[control.id] = control.type;
					}

					this.firstLoad.push(payload.connector.id);
					this.firstLoad = [...new Set(this.firstLoad)];

					// Get all current IDs from IndexedDB
					const allRecords = await getAllRecords<IConnectorControlDatabaseRecord>(DB_TABLE_CONNECTORS_CONTROLS);
					const indexedDbIds: string[] = allRecords.filter((record) => record.connector.id === payload.connector.id).map((record) => record.id);

					// Get the IDs from the latest changes
					const serverIds: string[] = Object.keys(this.data ?? {});

					// Find IDs that are in IndexedDB but not in the server response
					const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

					// Remove records that are no longer present on the server
					for (const id of idsToRemove) {
						await removeRecord(id, DB_TABLE_CONNECTORS_CONTROLS);

						delete this.meta[id];
					}
				} catch (e: any) {
					if (e instanceof AxiosError && e.status === 404) {
						try {
							const connectorsStore = storesManager.getStore(connectorsStoreKey);

							await connectorsStore.get({
								id: payload.connector.id,
							});
						} catch (e: any) {
							if (e instanceof ApiError && e.exception instanceof AxiosError && e.exception.status === 404) {
								const connectorsStore = storesManager.getStore(connectorsStoreKey);

								connectorsStore.unset({
									id: payload.connector.id,
								});

								return true;
							}
						}
					}

					throw new ApiError('devices-module.connector-controls.fetch.failed', e, 'Fetching controls failed.');
				} finally {
					this.semaphore.fetching.items = this.semaphore.fetching.items.filter((item) => item !== payload.connector.id);
				}

				return true;
			},

			/**
			 * Add new record
			 *
			 * @param {IConnectorControlsAddActionPayload} payload
			 */
			async add(payload: IConnectorControlsAddActionPayload): Promise<IConnectorControl> {
				const newControl = await storeRecordFactory({
					...{
						id: payload?.id,
						type: payload?.type,
						draft: payload?.draft,
						connectorId: payload.connector.id,
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
					const connectorsStore = storesManager.getStore(connectorsStoreKey);

					const connector = connectorsStore.findById(payload.connector.id);

					if (connector === null) {
						this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newControl.id);

						throw new Error('devices-module.connector-controls.get.failed');
					}

					try {
						const createdControl = await axios.post<IConnectorControlResponseJson>(
							`/${ModulePrefix.DEVICES}/v1/connectors/${payload.connector.id}/controls`,
							jsonApiFormatter.serialize({
								stuff: newControl,
							})
						);

						const createdControlModel = jsonApiFormatter.deserialize(createdControl.data) as IConnectorControlResponseModel;

						this.data[createdControlModel.id] = await storeRecordFactory({
							...createdControlModel,
							...{ connectorId: createdControlModel.connector.id },
						});

						await addRecord<IConnectorControlDatabaseRecord>(databaseRecordFactory(this.data[createdControlModel.id]), DB_TABLE_CONNECTORS_CONTROLS);

						this.meta[createdControlModel.id] = createdControlModel.type;

						return this.data[createdControlModel.id];
					} catch (e: any) {
						// Record could not be created on api, we have to remove it from database
						delete this.data[newControl.id];

						throw new ApiError('devices-module.connector-controls.create.failed', e, 'Create new control failed.');
					} finally {
						this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newControl.id);
					}
				}
			},

			/**
			 * Save draft record on server
			 *
			 * @param {IConnectorControlsSaveActionPayload} payload
			 */
			async save(payload: IConnectorControlsSaveActionPayload): Promise<IConnectorControl> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('devices-module.connector-controls.save.inProgress');
				}

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.connector-controls.save.failed');
				}

				this.semaphore.updating.push(payload.id);

				const recordToSave = this.data[payload.id];

				const connectorsStore = storesManager.getStore(connectorsStoreKey);

				const connector = connectorsStore.findById(recordToSave.connector.id);

				if (connector === null) {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

					throw new Error('devices-module.connector-controls.get.failed');
				}

				try {
					const savedControl = await axios.post<IConnectorControlResponseJson>(
						`/${ModulePrefix.DEVICES}/v1/connectors/${recordToSave.connector.id}/controls`,
						jsonApiFormatter.serialize({
							stuff: recordToSave,
						})
					);

					const savedControlModel = jsonApiFormatter.deserialize(savedControl.data) as IConnectorControlResponseModel;

					this.data[savedControlModel.id] = await storeRecordFactory({
						...savedControlModel,
						...{ connectorId: savedControlModel.connector.id },
					});

					await addRecord<IConnectorControlDatabaseRecord>(databaseRecordFactory(this.data[savedControlModel.id]), DB_TABLE_CONNECTORS_CONTROLS);

					this.meta[savedControlModel.id] = savedControlModel.type;

					return this.data[savedControlModel.id];
				} catch (e: any) {
					throw new ApiError('devices-module.connector-controls.save.failed', e, 'Save draft control failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}
			},

			/**
			 * Remove existing record from store and server
			 *
			 * @param {IConnectorControlsRemoveActionPayload} payload
			 */
			async remove(payload: IConnectorControlsRemoveActionPayload): Promise<boolean> {
				if (this.semaphore.deleting.includes(payload.id)) {
					throw new Error('devices-module.connector-controls.delete.inProgress');
				}

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.connector-controls.delete.failed');
				}

				this.semaphore.deleting.push(payload.id);

				const recordToDelete = this.data[payload.id];

				const connectorsStore = storesManager.getStore(connectorsStoreKey);

				const connector = connectorsStore.findById(recordToDelete.connector.id);

				if (connector === null) {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);

					throw new Error('devices-module.connector-controls.get.failed');
				}

				delete this.data[payload.id];

				await removeRecord(payload.id, DB_TABLE_CONNECTORS_CONTROLS);

				delete this.meta[payload.id];

				if (recordToDelete.draft) {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				} else {
					try {
						await axios.delete(`/${ModulePrefix.DEVICES}/v1/connectors/${recordToDelete.connector.id}/controls/${recordToDelete.id}`);
					} catch (e: any) {
						const connectorsStore = storesManager.getStore(connectorsStoreKey);

						const connector = connectorsStore.findById(recordToDelete.connector.id);

						if (connector !== null) {
							// Deleting entity on api failed, we need to refresh entity
							await this.get({ connector, id: payload.id });
						}

						throw new ApiError('devices-module.connector-controls.delete.failed', e, 'Delete control failed.');
					} finally {
						this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
					}
				}

				return true;
			},

			/**
			 * Transmit control command to server
			 *
			 * @param {IConnectorControlsTransmitCommandActionPayload} payload
			 */
			async transmitCommand(payload: IConnectorControlsTransmitCommandActionPayload): Promise<boolean> {
				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.connector-controls.transmit.failed');
				}

				const control = this.data[payload.id];

				const connectorsStore = storesManager.getStore(connectorsStoreKey);

				const connector = connectorsStore.findById(control.connector.id);

				if (connector === null) {
					throw new Error('devices-module.connector-controls.transmit.failed');
				}

				const { call } = useWampV1Client<{ data: string }>();

				try {
					const response = await call('', {
						routing_key: ActionRoutes.CONNECTOR_CONTROL,
						source: control.type.source,
						data: {
							action: ExchangeCommand.SET,
							connector: connector.id,
							control: control.id,
							expected_value: payload.value,
						},
					});

					if (get(response.data, 'response') === 'accepted') {
						return true;
					}
				} catch (e) {
					throw new Error('devices-module.connector-controls.transmit.failed');
				}

				throw new Error('devices-module.connector-controls.transmit.failed');
			},

			/**
			 * Receive data from sockets
			 *
			 * @param {IConnectorControlsSocketDataActionPayload} payload
			 */
			async socketData(payload: IConnectorControlsSocketDataActionPayload): Promise<boolean> {
				if (
					![
						RoutingKeys.CONNECTOR_CONTROL_DOCUMENT_REPORTED,
						RoutingKeys.CONNECTOR_CONTROL_DOCUMENT_CREATED,
						RoutingKeys.CONNECTOR_CONTROL_DOCUMENT_UPDATED,
						RoutingKeys.CONNECTOR_CONTROL_DOCUMENT_DELETED,
					].includes(payload.routingKey as RoutingKeys)
				) {
					return false;
				}

				const body: ConnectorControlDocument = JSON.parse(payload.data);

				const isValid = jsonSchemaValidator.compile<ConnectorControlDocument>(exchangeDocumentSchema);

				try {
					if (!isValid(body)) {
						return false;
					}
				} catch {
					return false;
				}

				if (payload.routingKey === RoutingKeys.CONNECTOR_CONTROL_DOCUMENT_DELETED) {
					await removeRecord(body.id, DB_TABLE_CONNECTORS_CONTROLS);

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
								connectorId: body.connector,
							},
						});

						if (!isEqual(JSON.parse(JSON.stringify(this.data[body.id])), JSON.parse(JSON.stringify(record)))) {
							this.data[body.id] = record;

							await addRecord<IConnectorControlDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CONNECTORS_CONTROLS);

							this.meta[record.id] = record.type;
						}
					} else {
						const connectorsStore = storesManager.getStore(connectorsStoreKey);

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
			 * @param {IConnectorControlsInsertDataActionPayload} payload
			 */
			async insertData(payload: IConnectorControlsInsertDataActionPayload) {
				this.data = this.data ?? {};

				let documents: ConnectorControlDocument[] = [];

				if (Array.isArray(payload.data)) {
					documents = payload.data;
				} else {
					documents = [payload.data];
				}

				const connectorIds = [];

				for (const doc of documents) {
					const isValid = jsonSchemaValidator.compile<ConnectorControlDocument>(exchangeDocumentSchema);

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
								entity: 'control',
							},
							name: doc.name,
							connectorId: doc.connector,
						},
					});

					if (documents.length === 1) {
						this.data[doc.id] = record;
					}

					await addRecord<IConnectorControlDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CONNECTORS_CONTROLS);

					this.meta[record.id] = record.type;

					connectorIds.push(doc.connector);
				}

				if (documents.length > 1) {
					const uniqueConnectorIds = [...new Set(connectorIds)];

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
			 * @param {IConnectorControlsLoadRecordActionPayload} payload
			 */
			async loadRecord(payload: IConnectorControlsLoadRecordActionPayload): Promise<boolean> {
				const record = await getRecord<IConnectorControlDatabaseRecord>(payload.id, DB_TABLE_CONNECTORS_CONTROLS);

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
			 * @param {IConnectorControlsLoadAllRecordsActionPayload} payload
			 */
			async loadAllRecords(payload?: IConnectorControlsLoadAllRecordsActionPayload): Promise<boolean> {
				const records = await getAllRecords<IConnectorControlDatabaseRecord>(DB_TABLE_CONNECTORS_CONTROLS);

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

export const registerConnectorsControlsStore = (
	pinia: Pinia
): Store<string, IConnectorControlsState, IConnectorControlsGetters, IConnectorControlsActions> => {
	return useConnectorControls(pinia);
};
