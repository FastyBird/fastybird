import { defineStore } from 'pinia';
import axios from 'axios';
import { Jsona } from 'jsona';
import Ajv from 'ajv/dist/2020';
import { v4 as uuid } from 'uuid';
import get from 'lodash/get';
import isEqual from 'lodash/isEqual';

import exchangeDocumentSchema from '@fastybird/metadata-library/resources/schemas/modules/devices-module/document.connector.control.json';
import { ConnectorControlDocument, DevicesModuleRoutes as RoutingKeys, ModulePrefix } from '@fastybird/metadata-library';

import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { useConnectors } from '../../models';
import { IConnector } from '../../models/types';

import {
	IConnectorControlsState,
	IConnectorControlsActions,
	IConnectorControlsGetters,
	IConnectorControl,
	IConnectorControlsAddActionPayload,
	IConnectorControlRecordFactoryPayload,
	IConnectorControlResponseModel,
	IConnectorControlResponseJson,
	IConnectorControlsResponseJson,
	IConnectorControlsGetActionPayload,
	IConnectorControlsFetchActionPayload,
	IConnectorControlsSaveActionPayload,
	IConnectorControlsRemoveActionPayload,
	IConnectorControlsSocketDataActionPayload,
	IConnectorControlsUnsetActionPayload,
	IConnectorControlsSetActionPayload,
} from './types';

const jsonSchemaValidator = new Ajv();

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const recordFactory = async (data: IConnectorControlRecordFactoryPayload): Promise<IConnectorControl> => {
	const connectorsStore = useConnectors();

	let connector = connectorsStore.findById(data.connectorId);

	if (connector === null) {
		if (!(await connectorsStore.get({ id: data.connectorId }))) {
			throw new Error("Connector for control couldn't be loaded from server");
		}

		connector = connectorsStore.findById(data.connectorId);

		if (connector === null) {
			throw new Error("Connector for control couldn't be loaded from store");
		}
	}

	return {
		id: get(data, 'id', uuid().toString()),
		type: { ...{ parent: 'connector', entity: 'control' }, ...data.type },

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

				data: {},
			};
		},

		getters: {
			firstLoadFinished: (state: IConnectorControlsState): ((connectorId: string) => boolean) => {
				return (connectorId) => state.firstLoad.includes(connectorId);
			},

			getting: (state: IConnectorControlsState): ((controlId: string) => boolean) => {
				return (controlId) => state.semaphore.fetching.item.includes(controlId);
			},

			fetching: (state: IConnectorControlsState): ((connectorId: string | null) => boolean) => {
				return (connectorId) =>
					connectorId !== null ? state.semaphore.fetching.items.includes(connectorId) : state.semaphore.fetching.items.length > 0;
			},

			findById: (state: IConnectorControlsState): ((id: string) => IConnectorControl | null) => {
				return (id) => {
					const control = Object.values(state.data).find((control) => control.id === id);

					return control ?? null;
				};
			},

			findByName: (state: IConnectorControlsState): ((connector: IConnector, name: string) => IConnectorControl | null) => {
				return (connector: IConnector, name) => {
					const control = Object.values(state.data).find((control) => {
						return control.connector.id === connector.id && control.name.toLowerCase() === name.toLowerCase();
					});

					return control ?? null;
				};
			},

			findForConnector: (state: IConnectorControlsState): ((connectorId: string) => IConnectorControl[]) => {
				return (connectorId: string): IConnectorControl[] => {
					return Object.values(state.data).filter((control) => control.connector.id === connectorId);
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
			 * @param {IConnectorControlsUnsetActionPayload} payload
			 */
			unset(payload: IConnectorControlsUnsetActionPayload): void {
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

				this.semaphore.fetching.item.push(payload.id);

				try {
					const controlResponse = await axios.get<IConnectorControlResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${payload.connector.id}/controls/${payload.id}`
					);

					const controlResponseModel = jsonApiFormatter.deserialize(controlResponse.data) as IConnectorControlResponseModel;

					this.data[controlResponseModel.id] = await recordFactory({
						...controlResponseModel,
						...{ connectorId: controlResponseModel.connector.id },
					});
				} catch (e: any) {
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

				this.semaphore.fetching.items.push(payload.connector.id);

				try {
					const controlsResponse = await axios.get<IConnectorControlsResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${payload.connector.id}/controls`
					);

					const controlsResponseModel = jsonApiFormatter.deserialize(controlsResponse.data) as IConnectorControlResponseModel[];

					for (const control of controlsResponseModel) {
						this.data[control.id] = await recordFactory({
							...control,
							...{ connectorId: control.connector.id },
						});
					}

					this.firstLoad.push(payload.connector.id);
				} catch (e: any) {
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
				const newControl = await recordFactory({
					...{
						id: payload?.id,
						type: payload?.type,
						draft: payload?.draft,
						connectorId: payload.connector.id,
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
						const createdControl = await axios.post<IConnectorControlResponseJson>(
							`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${payload.connector.id}/controls`,
							jsonApiFormatter.serialize({
								stuff: newControl,
							})
						);

						const createdControlModel = jsonApiFormatter.deserialize(createdControl.data) as IConnectorControlResponseModel;

						this.data[createdControlModel.id] = await recordFactory({
							...createdControlModel,
							...{ connectorId: createdControlModel.connector.id },
						});

						return this.data[createdControlModel.id];
					} catch (e: any) {
						// Transformer could not be created on api, we have to remove it from database
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

				if (!Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.connector-controls.save.failed');
				}

				this.semaphore.updating.push(payload.id);

				const recordToSave = this.data[payload.id];

				try {
					const savedControl = await axios.post<IConnectorControlResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${recordToSave.connector.id}/controls`,
						jsonApiFormatter.serialize({
							stuff: recordToSave,
						})
					);

					const savedControlModel = jsonApiFormatter.deserialize(savedControl.data) as IConnectorControlResponseModel;

					this.data[savedControlModel.id] = await recordFactory({
						...savedControlModel,
						...{ connectorId: savedControlModel.connector.id },
					});

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

				if (!Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.connector-controls.delete.failed');
				}

				this.semaphore.deleting.push(payload.id);

				const recordToDelete = this.data[payload.id];

				delete this.data[payload.id];

				if (recordToDelete.draft) {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				} else {
					try {
						await axios.delete(`/${ModulePrefix.MODULE_DEVICES}/v1/connectors/${recordToDelete.connector.id}/controls/${recordToDelete.id}`);
					} catch (e: any) {
						const connectorsStore = useConnectors();

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
					if (body.id in this.data) {
						delete this.data[body.id];
					}
				} else {
					if (body.id in this.data) {
						const record = await recordFactory({
							...this.data[body.id],
							...{
								name: body.name,
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
