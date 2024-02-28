import { defineStore } from 'pinia';
import axios from 'axios';
import { Jsona } from 'jsona';
import Ajv from 'ajv/dist/2020';
import { v4 as uuid } from 'uuid';
import get from 'lodash/get';
import isEqual from 'lodash/isEqual';

import exchangeDocumentSchema from '../../../resources/schemas/document.connector.json';
import {
	ConnectorCategory,
	ConnectorDocument,
	DevicePropertyIdentifier,
	DevicesModuleRoutes as RoutingKeys,
	ModulePrefix,
} from '@fastybird/metadata-library';

import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { useConnectorControls, useConnectorProperties, useDevices } from '../../models';
import { IConnectorProperty, IConnectorPropertyResponseModel, IConnectorControlResponseModel, IPlainRelation } from '../../models/types';

import {
	IConnectorsState,
	IConnectorsActions,
	IConnectorsGetters,
	IConnector,
	IConnectorsAddActionPayload,
	IConnectorsFetchActionPayload,
	IConnectorsGetActionPayload,
	IConnectorRecordFactoryPayload,
	IConnectorsRemoveActionPayload,
	IConnectorsSetActionPayload,
	IConnectorResponseJson,
	IConnectorResponseModel,
	IConnectorsSaveActionPayload,
	IConnectorsSocketDataActionPayload,
	IConnectorsResponseJson,
	IConnectorsEditActionPayload,
} from './types';

const jsonSchemaValidator = new Ajv();

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const recordFactory = (data: IConnectorRecordFactoryPayload): IConnector => {
	const record: IConnector = {
		id: get(data, 'id', uuid().toString()),
		type: { ...{ entity: 'connector' }, ...data.type },

		draft: get(data, 'draft', false),

		category: data.category,
		identifier: data.identifier,
		name: get(data, 'name', null),
		comment: get(data, 'commend', null),
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

const addPropertiesRelations = (connector: IConnector, properties: (IConnectorPropertyResponseModel | IPlainRelation)[]): void => {
	const propertiesStore = useConnectorProperties();

	properties.forEach((property) => {
		if ('identifier' in property) {
			propertiesStore.set({
				data: {
					...property,
					...{
						connectorId: connector.id,
					},
				},
			});
		}
	});
};

const addControlsRelations = (connector: IConnector, controls: (IConnectorControlResponseModel | IPlainRelation)[]): void => {
	const controlsStore = useConnectorControls();

	controls.forEach((control) => {
		if ('identifier' in control) {
			controlsStore.set({
				data: {
					...control,
					...{
						connectorId: connector.id,
					},
				},
			});
		}
	});
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

			data: {},
		};
	},

	getters: {
		firstLoadFinished: (state: IConnectorsState): boolean => {
			return state.firstLoad;
		},

		getting: (state: IConnectorsState): ((id: string) => boolean) => {
			return (id: string): boolean => state.semaphore.fetching.item.includes(id);
		},

		fetching: (state: IConnectorsState): boolean => {
			return state.semaphore.fetching.items;
		},

		findById: (state: IConnectorsState): ((id: string) => IConnector | null) => {
			return (id: string): IConnector | null => {
				return id in state.data ? state.data[id] : null;
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
			const record = await recordFactory(payload.data);

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
		 * @param {IConnectorsGetActionPayload} payload
		 */
		async get(payload: IConnectorsGetActionPayload): Promise<boolean> {
			if (this.semaphore.fetching.item.includes(payload.id)) {
				return false;
			}

			this.semaphore.fetching.item.push(payload.id);

			try {
				const connectorResponse = await axios.get<IConnectorResponseJson>(
					`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${payload.id}?include=properties,controls`
				);

				const connectorResponseModel = jsonApiFormatter.deserialize(connectorResponse.data) as IConnectorResponseModel;

				this.data[connectorResponseModel.id] = recordFactory(connectorResponseModel);

				addPropertiesRelations(this.data[connectorResponseModel.id], connectorResponseModel.properties);
				addControlsRelations(this.data[connectorResponseModel.id], connectorResponseModel.controls);

				if (payload.withDevices) {
					const devicesStore = useDevices();

					await devicesStore.fetch({ connector: this.data[connectorResponseModel.id], withChannels: true });
				}
			} catch (e: any) {
				throw new ApiError('devices-module.connectors.get.failed', e, 'Fetching connector failed.');
			} finally {
				this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
			}

			return true;
		},

		/**
		 * Fetch all records from server
		 *
		 * @param {IConnectorsFetchActionPayload} payload
		 */
		async fetch(payload: IConnectorsFetchActionPayload): Promise<boolean> {
			if (this.semaphore.fetching.items) {
				return false;
			}

			this.semaphore.fetching.items = true;

			const devicesStore = useDevices();

			try {
				const connectorsResponse = await axios.get<IConnectorsResponseJson>(
					`/${ModulePrefix.MODULE_DEVICES}/v1/connectors?include=properties,controls`
				);

				const connectorsResponseModel = jsonApiFormatter.deserialize(connectorsResponse.data) as IConnectorResponseModel[];

				for (const connector of connectorsResponseModel) {
					this.data[connector.id] = recordFactory(connector);

					addPropertiesRelations(this.data[connector.id], connector.properties);
					addControlsRelations(this.data[connector.id], connector.controls);

					if (payload.withDevices) {
						await devicesStore.fetch({ connector: this.data[connector.id], withChannels: true });
					}
				}

				this.firstLoad = true;
			} catch (e: any) {
				throw new ApiError('devices-module.connectors.fetch.failed', e, 'Fetching connectors failed.');
			} finally {
				this.semaphore.fetching.items = false;
			}

			return true;
		},

		/**
		 * Add new record
		 *
		 * @param {IConnectorsAddActionPayload} payload
		 */
		async add(payload: IConnectorsAddActionPayload): Promise<IConnector> {
			const newConnector = recordFactory({
				...payload.data,
				...{ id: payload?.id, type: payload?.type, category: ConnectorCategory.GENERIC, draft: payload?.draft },
			});

			this.semaphore.creating.push(newConnector.id);

			this.data[newConnector.id] = newConnector;

			if (newConnector.draft) {
				this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newConnector.id);

				return newConnector;
			} else {
				try {
					const createdConnector = await axios.post<IConnectorResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/connectors?include=properties,controls`,
						jsonApiFormatter.serialize({
							stuff: newConnector,
						})
					);

					const createdConnectorModel = jsonApiFormatter.deserialize(createdConnector.data) as IConnectorResponseModel;

					this.data[createdConnectorModel.id] = recordFactory(createdConnectorModel);

					addPropertiesRelations(this.data[createdConnectorModel.id], createdConnectorModel.properties);
					addControlsRelations(this.data[createdConnectorModel.id], createdConnectorModel.controls);

					return this.data[createdConnectorModel.id];
				} catch (e: any) {
					// Record could not be created on api, we have to remove it from database
					delete this.data[newConnector.id];

					throw new ApiError('devices-module.connectors.create.failed', e, 'Create new connector failed.');
				} finally {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newConnector.id);
				}
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

			if (!Object.keys(this.data).includes(payload.id)) {
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
						`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${payload.id}?include=properties,controls`,
						jsonApiFormatter.serialize({
							stuff: updatedRecord,
						})
					);

					const updatedConnectorModel = jsonApiFormatter.deserialize(updatedConnector.data) as IConnectorResponseModel;

					this.data[updatedConnectorModel.id] = recordFactory(updatedConnectorModel);

					addPropertiesRelations(this.data[updatedConnectorModel.id], updatedConnectorModel.properties);
					addControlsRelations(this.data[updatedConnectorModel.id], updatedConnectorModel.controls);

					return this.data[updatedConnectorModel.id];
				} catch (e: any) {
					// Updating record on api failed, we need to refresh record
					await this.get({ id: payload.id });

					throw new ApiError('devices-module.connectors.update.failed', e, 'Edit connector failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}
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

			if (!Object.keys(this.data).includes(payload.id)) {
				throw new Error('devices-module.connectors.save.failed');
			}

			this.semaphore.updating.push(payload.id);

			const recordToSave = this.data[payload.id];

			try {
				const savedConnector = await axios.post<IConnectorResponseJson>(
					`/${ModulePrefix.MODULE_DEVICES}/v1/connectors?include=properties,controls`,
					jsonApiFormatter.serialize({
						stuff: recordToSave,
					})
				);

				const savedConnectorModel = jsonApiFormatter.deserialize(savedConnector.data) as IConnectorResponseModel;

				this.data[savedConnectorModel.id] = recordFactory(savedConnectorModel);

				addPropertiesRelations(this.data[savedConnectorModel.id], savedConnectorModel.properties);
				addControlsRelations(this.data[savedConnectorModel.id], savedConnectorModel.controls);

				return this.data[savedConnectorModel.id];
			} catch (e: any) {
				throw new ApiError('devices-module.connectors.save.failed', e, 'Save draft connector failed.');
			} finally {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
			}
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

			if (!Object.keys(this.data).includes(payload.id)) {
				return true;
			}

			const propertiesStore = useConnectorProperties();
			const controlsStore = useConnectorControls();

			this.semaphore.deleting.push(payload.id);

			const recordToDelete = this.data[payload.id];

			delete this.data[payload.id];

			if (recordToDelete.draft) {
				this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);

				propertiesStore.unset({ connector: recordToDelete });
				controlsStore.unset({ connector: recordToDelete });
			} else {
				try {
					await axios.delete(`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${payload.id}`);

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
				if (body.id in this.data) {
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

				if (body.id in this.data) {
					const record = recordFactory({
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
	},
});
