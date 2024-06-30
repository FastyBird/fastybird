import { ActionRoutes, ControlAction, ChannelControlDocument, DevicesModuleRoutes as RoutingKeys, ModulePrefix } from '@fastybird/metadata-library';
import { useWampV1Client } from '@fastybird/vue-wamp-v1';
import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios from 'axios';
import { Jsona } from 'jsona';
import get from 'lodash.get';
import isEqual from 'lodash.isequal';
import { defineStore } from 'pinia';
import { v4 as uuid } from 'uuid';

import exchangeDocumentSchema from '../../../resources/schemas/document.channel.control.json';

import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { useChannels } from '../../models';
import { IChannel } from '../channels/types';
import { addRecord, getAllRecords, getRecord, removeRecord, DB_TABLE_CHANNELS_CONTROLS } from '../../utilities/database';

import {
	IChannelControl,
	IChannelControlDatabaseRecord,
	IChannelControlMeta,
	IChannelControlRecordFactoryPayload,
	IChannelControlResponseJson,
	IChannelControlResponseModel,
	IChannelControlsActions,
	IChannelControlsAddActionPayload,
	IChannelControlsFetchActionPayload,
	IChannelControlsGetActionPayload,
	IChannelControlsGetters,
	IChannelControlsInsertDataActionPayload,
	IChannelControlsLoadAllRecordsActionPayload,
	IChannelControlsLoadRecordActionPayload,
	IChannelControlsRemoveActionPayload,
	IChannelControlsResponseJson,
	IChannelControlsSaveActionPayload,
	IChannelControlsSetActionPayload,
	IChannelControlsSocketDataActionPayload,
	IChannelControlsState,
	IChannelControlsTransmitCommandActionPayload,
	IChannelControlsUnsetActionPayload,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (data: IChannelControlRecordFactoryPayload): Promise<IChannelControl> => {
	const channelsStore = useChannels();

	let channel = 'channel' in data ? get(data, 'channel', null) : null;

	let channelMeta = data.channelId ? channelsStore.findMeta(data.channelId) : null;

	if (channel === null && channelMeta !== null) {
		channel = {
			id: data.channelId as string,
			type: channelMeta,
		};
	}

	if (channel === null) {
		if (!('channelId' in data)) {
			throw new Error("Channel for control couldn't be loaded from store");
		}

		if (!(await channelsStore.get({ id: data.channelId as string, refresh: false }))) {
			throw new Error("Channel for control couldn't be loaded from server");
		}

		channelMeta = channelsStore.findMeta(data.channelId as string);

		if (channelMeta === null) {
			throw new Error("Channel for control couldn't be loaded from store");
		}

		channel = {
			id: data.channelId as string,
			type: channelMeta,
		};
	}

	return {
		id: get(data, 'id', uuid().toString()),
		type: data.type,

		draft: get(data, 'draft', false),

		name: data.name,

		// Relations
		relationshipNames: ['channel'],

		channel: {
			id: channel.id,
			type: channel.type,
		},
	} as IChannelControl;
};

const databaseRecordFactory = (record: IChannelControl): IChannelControlDatabaseRecord => {
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

		channel: {
			id: record.channel.id,
			type: {
				type: record.channel.type.type,
				source: record.channel.type.source,
				entity: record.channel.type.entity,
			},
		},
	};
};

export const useChannelControls = defineStore<string, IChannelControlsState, IChannelControlsGetters, IChannelControlsActions>(
	'devices_module_channels_controls',
	{
		state: (): IChannelControlsState => {
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
			getting: (state: IChannelControlsState): ((id: IChannelControl['id']) => boolean) => {
				return (id: IChannelControl['id']): boolean => state.semaphore.fetching.item.includes(id);
			},

			fetching: (state: IChannelControlsState): ((channelId: IChannel['id'] | null) => boolean) => {
				return (channelId: IChannel['id'] | null): boolean =>
					channelId !== null ? state.semaphore.fetching.items.includes(channelId) : state.semaphore.fetching.items.length > 0;
			},

			findById: (state: IChannelControlsState): ((id: IChannelControl['id']) => IChannelControl | null) => {
				return (id: IChannelControl['id']): IChannelControl | null => {
					const control: IChannelControl | undefined = Object.values(state.data ?? {}).find((control: IChannelControl): boolean => control.id === id);

					return control ?? null;
				};
			},

			findByName: (state: IChannelControlsState): ((channel: IChannel, name: IChannelControl['name']) => IChannelControl | null) => {
				return (channel: IChannel, name: IChannelControl['name']): IChannelControl | null => {
					const control: IChannelControl | undefined = Object.values(state.data ?? {}).find((control: IChannelControl): boolean => {
						return control.channel.id === channel.id && control.name.toLowerCase() === name.toLowerCase();
					});

					return control ?? null;
				};
			},

			findForChannel: (state: IChannelControlsState): ((channelId: IChannel['id']) => IChannelControl[]) => {
				return (channelId: IChannel['id']): IChannelControl[] => {
					return Object.values(state.data ?? {}).filter((control: IChannelControl): boolean => control.channel.id === channelId);
				};
			},

			findMeta: (state: IChannelControlsState): ((id: IChannelControl['id']) => IChannelControlMeta | null) => {
				return (id: IChannelControl['id']): IChannelControlMeta | null => {
					return id in state.meta ? state.meta[id] : null;
				};
			},
		},

		actions: {
			/**
			 * Set record from via other store
			 *
			 * @param {IChannelControlsSetActionPayload} payload
			 */
			async set(payload: IChannelControlsSetActionPayload): Promise<IChannelControl> {
				if (this.data && payload.data.id && payload.data.id in this.data) {
					const record = await storeRecordFactory({ ...this.data[payload.data.id], ...payload.data });

					return (this.data[record.id] = record);
				}

				const record = await storeRecordFactory(payload.data);

				await addRecord<IChannelControlDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CHANNELS_CONTROLS);

				this.meta[record.id] = record.type;

				this.data = this.data ?? {};
				return (this.data[record.id] = record);
			},

			/**
			 * Remove records for given relation or record by given identifier
			 *
			 * @param {IChannelControlsUnsetActionPayload} payload
			 */
			async unset(payload: IChannelControlsUnsetActionPayload): Promise<void> {
				if (!this.data) {
					return;
				}

				if (payload.channel !== undefined) {
					const items = this.findForChannel(payload.channel.id);

					for (const item of items) {
						if (item.id in (this.data ?? {})) {
							await removeRecord(item.id, DB_TABLE_CHANNELS_CONTROLS);

							delete this.meta[item.id];

							delete (this.data ?? {})[item.id];
						}
					}

					return;
				} else if (payload.id !== undefined) {
					await removeRecord(payload.id, DB_TABLE_CHANNELS_CONTROLS);

					delete this.meta[payload.id];

					delete this.data[payload.id];

					return;
				}

				throw new Error('You have to provide at least channel or control id');
			},

			/**
			 * Get one record from server
			 *
			 * @param {IChannelControlsGetActionPayload} payload
			 */
			async get(payload: IChannelControlsGetActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.item.includes(payload.id)) {
					return false;
				}

				const fromDatabase = await this.loadRecord({ id: payload.id });

				if (fromDatabase && payload.refresh === false) {
					return true;
				}

				this.semaphore.fetching.item.push(payload.id);

				try {
					const controlResponse = await axios.get<IChannelControlResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/channels/${payload.channel.id}/controls/${payload.id}`
					);

					const controlResponseModel = jsonApiFormatter.deserialize(controlResponse.data) as IChannelControlResponseModel;

					this.data = this.data ?? {};
					this.data[controlResponseModel.id] = await storeRecordFactory({
						...controlResponseModel,
						...{ channelId: controlResponseModel.channel.id },
					});

					await addRecord<IChannelControlDatabaseRecord>(databaseRecordFactory(this.data[controlResponseModel.id]), DB_TABLE_CHANNELS_CONTROLS);

					this.meta[controlResponseModel.id] = controlResponseModel.type;
				} catch (e: any) {
					throw new ApiError('devices-module.channel-controls.get.failed', e, 'Fetching control failed.');
				} finally {
					this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
				}

				return true;
			},

			/**
			 * Fetch all records from server
			 *
			 * @param {IChannelControlsFetchActionPayload} payload
			 */
			async fetch(payload: IChannelControlsFetchActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.items.includes(payload.channel.id)) {
					return false;
				}

				const fromDatabase = await this.loadAllRecords({ channel: payload.channel });

				if (fromDatabase && payload?.refresh === false) {
					return true;
				}

				this.semaphore.fetching.items.push(payload.channel.id);

				try {
					const controlsResponse = await axios.get<IChannelControlsResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/channels/${payload.channel.id}/controls`
					);

					const controlsResponseModel = jsonApiFormatter.deserialize(controlsResponse.data) as IChannelControlResponseModel[];

					for (const control of controlsResponseModel) {
						this.data = this.data ?? {};
						this.data[control.id] = await storeRecordFactory({
							...control,
							...{ channelId: control.channel.id },
						});

						await addRecord<IChannelControlDatabaseRecord>(databaseRecordFactory(this.data[control.id]), DB_TABLE_CHANNELS_CONTROLS);

						this.meta[control.id] = control.type;
					}

					// Get all current IDs from IndexedDB
					const allRecords = await getAllRecords<IChannelControlDatabaseRecord>(DB_TABLE_CHANNELS_CONTROLS);
					const indexedDbIds: string[] = allRecords.filter((record) => record.channel.id === payload.channel.id).map((record) => record.id);

					// Get the IDs from the latest changes
					const serverIds: string[] = Object.keys(this.data ?? {});

					// Find IDs that are in IndexedDB but not in the server response
					const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

					// Remove records that are no longer present on the server
					for (const id of idsToRemove) {
						await removeRecord(id, DB_TABLE_CHANNELS_CONTROLS);

						delete this.meta[id];
					}
				} catch (e: any) {
					throw new ApiError('devices-module.channel-controls.fetch.failed', e, 'Fetching controls failed.');
				} finally {
					this.semaphore.fetching.items = this.semaphore.fetching.items.filter((item) => item !== payload.channel.id);
				}

				return true;
			},

			/**
			 * Add new record
			 *
			 * @param {IChannelControlsAddActionPayload} payload
			 */
			async add(payload: IChannelControlsAddActionPayload): Promise<IChannelControl> {
				const newControl = await storeRecordFactory({
					...{
						id: payload?.id,
						type: payload?.type,
						draft: payload?.draft,
						channelId: payload.channel.id,
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
					const channelsStore = useChannels();

					const channel = channelsStore.findById(payload.channel.id);

					if (channel === null) {
						this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newControl.id);

						throw new Error('devices-module.channel-controls.get.failed');
					}

					try {
						const createdControl = await axios.post<IChannelControlResponseJson>(
							`/${ModulePrefix.MODULE_DEVICES}/v1/channels/${payload.channel.id}/controls`,
							jsonApiFormatter.serialize({
								stuff: newControl,
							})
						);

						const createdControlModel = jsonApiFormatter.deserialize(createdControl.data) as IChannelControlResponseModel;

						this.data[createdControlModel.id] = await storeRecordFactory({
							...createdControlModel,
							...{ channelId: createdControlModel.channel.id },
						});

						await addRecord<IChannelControlDatabaseRecord>(databaseRecordFactory(this.data[createdControlModel.id]), DB_TABLE_CHANNELS_CONTROLS);

						this.meta[createdControlModel.id] = createdControlModel.type;

						return this.data[createdControlModel.id];
					} catch (e: any) {
						// Record could not be created on api, we have to remove it from database
						delete this.data[newControl.id];

						throw new ApiError('devices-module.channel-controls.create.failed', e, 'Create new control failed.');
					} finally {
						this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newControl.id);
					}
				}
			},

			/**
			 * Save draft record on server
			 *
			 * @param {IChannelControlsSaveActionPayload} payload
			 */
			async save(payload: IChannelControlsSaveActionPayload): Promise<IChannelControl> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('devices-module.channel-controls.save.inProgress');
				}

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.channel-controls.save.failed');
				}

				this.semaphore.updating.push(payload.id);

				const recordToSave = this.data[payload.id];

				const channelsStore = useChannels();

				const channel = channelsStore.findById(recordToSave.channel.id);

				if (channel === null) {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

					throw new Error('devices-module.channel-controls.get.failed');
				}

				try {
					const savedControl = await axios.post<IChannelControlResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/channels/${recordToSave.channel.id}/controls`,
						jsonApiFormatter.serialize({
							stuff: recordToSave,
						})
					);

					const savedControlModel = jsonApiFormatter.deserialize(savedControl.data) as IChannelControlResponseModel;

					this.data[savedControlModel.id] = await storeRecordFactory({
						...savedControlModel,
						...{ channelId: savedControlModel.channel.id },
					});

					await addRecord<IChannelControlDatabaseRecord>(databaseRecordFactory(this.data[savedControlModel.id]), DB_TABLE_CHANNELS_CONTROLS);

					this.meta[savedControlModel.id] = savedControlModel.type;

					return this.data[savedControlModel.id];
				} catch (e: any) {
					throw new ApiError('devices-module.channel-controls.save.failed', e, 'Save draft control failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}
			},

			/**
			 * Remove existing record from store and server
			 *
			 * @param {IChannelControlsRemoveActionPayload} payload
			 */
			async remove(payload: IChannelControlsRemoveActionPayload): Promise<boolean> {
				if (this.semaphore.deleting.includes(payload.id)) {
					throw new Error('devices-module.channel-controls.delete.inProgress');
				}

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.channel-controls.delete.failed');
				}

				this.semaphore.deleting.push(payload.id);

				const recordToDelete = this.data[payload.id];

				const channelsStore = useChannels();

				const channel = channelsStore.findById(recordToDelete.channel.id);

				if (channel === null) {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);

					throw new Error('devices-module.channel-controls.get.failed');
				}

				delete this.data[payload.id];

				await removeRecord(payload.id, DB_TABLE_CHANNELS_CONTROLS);

				delete this.meta[payload.id];

				if (recordToDelete.draft) {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				} else {
					try {
						await axios.delete(`/${ModulePrefix.MODULE_DEVICES}/v1/channels/${recordToDelete.channel.id}/controls/${recordToDelete.id}`);
					} catch (e: any) {
						const channelsStore = useChannels();

						const channel = channelsStore.findById(recordToDelete.channel.id);

						if (channel !== null) {
							// Deleting entity on api failed, we need to refresh entity
							await this.get({ channel, id: payload.id });
						}

						throw new ApiError('devices-module.channel-controls.delete.failed', e, 'Delete control failed.');
					} finally {
						this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
					}
				}

				return true;
			},

			/**
			 * Transmit control command to server
			 *
			 * @param {IChannelControlsTransmitCommandActionPayload} payload
			 */
			async transmitCommand(payload: IChannelControlsTransmitCommandActionPayload): Promise<boolean> {
				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.channel-controls.transmit.failed');
				}

				const control = this.data[payload.id];

				const channelsStore = useChannels();

				const channel = channelsStore.findById(control.channel.id);

				if (channel === null) {
					throw new Error('devices-module.channel-controls.transmit.failed');
				}

				const { call } = useWampV1Client<{ data: string }>();

				try {
					const response = await call('', {
						routing_key: ActionRoutes.CHANNEL_CONTROL,
						source: control.type.source,
						data: {
							action: ControlAction.SET,
							channel: channel.id,
							control: control.id,
							expected_value: payload.value,
						},
					});

					if (get(response.data, 'response') === 'accepted') {
						return true;
					}
				} catch (e) {
					throw new Error('devices-module.channel-controls.transmit.failed');
				}

				throw new Error('devices-module.channel-controls.transmit.failed');
			},

			/**
			 * Receive data from sockets
			 *
			 * @param {IChannelControlsSocketDataActionPayload} payload
			 */
			async socketData(payload: IChannelControlsSocketDataActionPayload): Promise<boolean> {
				if (
					![
						RoutingKeys.CHANNEL_CONTROL_DOCUMENT_REPORTED,
						RoutingKeys.CHANNEL_CONTROL_DOCUMENT_CREATED,
						RoutingKeys.CHANNEL_CONTROL_DOCUMENT_UPDATED,
						RoutingKeys.CHANNEL_CONTROL_DOCUMENT_DELETED,
					].includes(payload.routingKey as RoutingKeys)
				) {
					return false;
				}

				const body: ChannelControlDocument = JSON.parse(payload.data);

				const isValid = jsonSchemaValidator.compile<ChannelControlDocument>(exchangeDocumentSchema);

				try {
					if (!isValid(body)) {
						return false;
					}
				} catch {
					return false;
				}

				if (payload.routingKey === RoutingKeys.CHANNEL_CONTROL_DOCUMENT_DELETED) {
					await removeRecord(body.id, DB_TABLE_CHANNELS_CONTROLS);

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
								channelId: body.channel,
							},
						});

						if (!isEqual(JSON.parse(JSON.stringify(this.data[body.id])), JSON.parse(JSON.stringify(record)))) {
							this.data[body.id] = record;

							await addRecord<IChannelControlDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CHANNELS_CONTROLS);

							this.meta[record.id] = record.type;
						}
					} else {
						const channelsStore = useChannels();

						const channel = channelsStore.findById(body.channel);

						if (channel !== null) {
							try {
								await this.get({
									channel,
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
			 * @param {IChannelControlsInsertDataActionPayload} payload
			 */
			async insertData(payload: IChannelControlsInsertDataActionPayload) {
				this.data = this.data ?? {};

				let documents: ChannelControlDocument[] = [];

				if (Array.isArray(payload.data)) {
					documents = payload.data;
				} else {
					documents = [payload.data];
				}

				const channelIds = [];

				for (const doc of documents) {
					const isValid = jsonSchemaValidator.compile<ChannelControlDocument>(exchangeDocumentSchema);

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
								parent: 'channel',
								entity: 'control',
							},
							name: doc.name,
							channelId: doc.channel,
						},
					});

					if (documents.length === 1) {
						this.data[doc.id] = record;
					}

					await addRecord<IChannelControlDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CHANNELS_CONTROLS);

					this.meta[record.id] = record.type;

					channelIds.push(doc.channel);
				}

				return true;
			},

			/**
			 * Load record from database
			 *
			 * @param {IChannelControlsLoadRecordActionPayload} payload
			 */
			async loadRecord(payload: IChannelControlsLoadRecordActionPayload): Promise<boolean> {
				const record = await getRecord<IChannelControlDatabaseRecord>(payload.id, DB_TABLE_CHANNELS_CONTROLS);

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
			 * @param {IChannelControlsLoadAllRecordsActionPayload} payload
			 */
			async loadAllRecords(payload?: IChannelControlsLoadAllRecordsActionPayload): Promise<boolean> {
				const records = await getAllRecords<IChannelControlDatabaseRecord>(DB_TABLE_CHANNELS_CONTROLS);

				this.data = this.data ?? {};

				for (const record of records) {
					if (payload?.channel && payload?.channel.id !== record?.channel.id) {
						continue;
					}

					this.data[record.id] = await storeRecordFactory(record);
				}

				return true;
			},
		},
	}
);
