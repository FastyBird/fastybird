import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios, { AxiosResponse } from 'axios';
import { Jsona } from 'jsona';
import get from 'lodash.get';
import isEqual from 'lodash.isequal';
import { defineStore } from 'pinia';
import { v4 as uuid } from 'uuid';

import { ChannelCategory, ChannelDocument, DevicesModuleRoutes as RoutingKeys, ModulePrefix } from '@fastybird/metadata-library';

import exchangeDocumentSchema from '../../../resources/schemas/document.channel.json';

import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { useChannelControls, useChannelProperties, useDevices } from '../../models';
import {
	IChannelControlResponseModel,
	IChannelMeta,
	IChannelPropertyResponseModel,
	IChannelsInsertDataActionPayload,
	IChannelsLoadAllRecordsActionPayload,
	IChannelsLoadRecordActionPayload,
	IDevice,
	IPlainRelation,
} from '../../models/types';
import { addRecord, getAllRecords, getRecord, removeRecord, DB_TABLE_CHANNELS } from '../../utilities/database';

import {
	IChannel,
	IChannelDatabaseRecord,
	IChannelRecordFactoryPayload,
	IChannelResponseJson,
	IChannelResponseModel,
	IChannelsActions,
	IChannelsAddActionPayload,
	IChannelsEditActionPayload,
	IChannelsFetchActionPayload,
	IChannelsGetActionPayload,
	IChannelsGetters,
	IChannelsRemoveActionPayload,
	IChannelsResponseJson,
	IChannelsSaveActionPayload,
	IChannelsSetActionPayload,
	IChannelsSocketDataActionPayload,
	IChannelsState,
	IChannelsUnsetActionPayload,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (data: IChannelRecordFactoryPayload): Promise<IChannel> => {
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
			throw new Error("Device for channel couldn't be loaded from store");
		}

		if (!(await devicesStore.get({ id: data.deviceId as string, refresh: false }))) {
			throw new Error("Device for channel couldn't be loaded from server");
		}

		deviceMeta = devicesStore.findMeta(data.deviceId as string);

		if (deviceMeta === null) {
			throw new Error("Device for channel couldn't be loaded from store");
		}

		device = {
			id: data.deviceId as string,
			type: deviceMeta,
		};
	}

	const record: IChannel = {
		id: get(data, 'id', uuid().toString()),
		type: data.type,

		draft: get(data, 'draft', false),

		category: data.category,
		identifier: data.identifier,
		name: get(data, 'name', null),
		comment: get(data, 'comment', null),

		// Relations
		relationshipNames: ['device', 'properties', 'controls'],

		controls: [],
		properties: [],

		device: {
			id: device.id,
			type: device.type,
		},

		// Transformer transformers
		get hasComment(): boolean {
			return this.comment !== null && this.comment !== '';
		},
	};

	record.relationshipNames.forEach((relationName) => {
		if (relationName === 'properties' || relationName === 'controls') {
			get(data, relationName, []).forEach((relation: any): void => {
				if (get(relation, 'id', null) !== null && get(relation, 'type', null) !== null) {
					(record[relationName] as IPlainRelation[]).push({
						id: get(relation, 'id', null),
						type: get(relation, 'type', null),
					});
				}
			});
		}
	});

	return record;
};

const databaseRecordFactory = (record: IChannel): IChannelDatabaseRecord => {
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

		relationshipNames: record.relationshipNames.map((name) => name),

		controls: record.controls.map((control) => ({
			id: control.id,
			type: { type: control.type.type, source: control.type.source, entity: control.type.entity, parent: control.type.parent },
		})),
		properties: record.properties.map((property) => ({
			id: property.id,
			type: { type: property.type.type, source: property.type.source, entity: property.type.entity, parent: property.type.parent },
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

const addPropertiesRelations = async (channel: IChannel, properties: (IChannelPropertyResponseModel | IPlainRelation)[]): Promise<void> => {
	const propertiesStore = useChannelProperties();

	for (const property of properties) {
		if ('identifier' in property) {
			await propertiesStore.set({
				data: {
					...property,
					...{
						channelId: channel.id,
					},
				},
			});
		}
	}
};

const addControlsRelations = async (channel: IChannel, controls: (IChannelControlResponseModel | IPlainRelation)[]): Promise<void> => {
	const controlsStore = useChannelControls();

	for (const control of controls) {
		if ('identifier' in control) {
			await controlsStore.set({
				data: {
					...control,
					...{
						channelId: channel.id,
					},
				},
			});
		}
	}
};

export const useChannels = defineStore<string, IChannelsState, IChannelsGetters, IChannelsActions>('devices_module_channels', {
	state: (): IChannelsState => {
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
		firstLoadFinished: (state: IChannelsState): ((deviceId?: IDevice['id'] | null) => boolean) => {
			return (deviceId: IDevice['id'] | null = null): boolean =>
				deviceId !== null ? state.firstLoad.includes(deviceId) : state.firstLoad.includes('all');
		},

		getting: (state: IChannelsState): ((id: IChannel['id']) => boolean) => {
			return (id: IChannel['id']): boolean => state.semaphore.fetching.item.includes(id);
		},

		fetching: (state: IChannelsState): ((deviceId?: IDevice['id'] | null) => boolean) => {
			return (deviceId: IDevice['id'] | null = null): boolean =>
				deviceId !== null ? state.semaphore.fetching.items.includes(deviceId) : state.semaphore.fetching.items.includes('all');
		},

		findById: (state: IChannelsState): ((id: IChannel['id']) => IChannel | null) => {
			return (id: IChannel['id']): IChannel | null => {
				const channel: IChannel | undefined = Object.values(state.data ?? {}).find((channel: IChannel): boolean => channel.id === id);

				return channel ?? null;
			};
		},

		findForDevice: (state: IChannelsState): ((deviceId: IDevice['id']) => IChannel[]) => {
			return (deviceId: IDevice['id']): IChannel[] => {
				return Object.values(state.data ?? {}).filter((channel: IChannel): boolean => channel.device.id === deviceId);
			};
		},

		findAll: (state: IChannelsState): (() => IChannel[]) => {
			return (): IChannel[] => {
				return Object.values(state.data ?? {});
			};
		},

		findMeta: (state: IChannelsState): ((id: IChannel['id']) => IChannelMeta | null) => {
			return (id: IChannel['id']): IChannelMeta | null => {
				return id in state.meta ? state.meta[id] : null;
			};
		},
	},

	actions: {
		/**
		 * Set record from via other store
		 *
		 * @param {IChannelsSetActionPayload} payload
		 */
		async set(payload: IChannelsSetActionPayload): Promise<IChannel> {
			if (payload.data.id && this.data && payload.data.id in this.data) {
				const record = await storeRecordFactory({ ...this.data[payload.data.id], ...payload.data });

				if ('properties' in payload.data && Array.isArray(payload.data.properties)) {
					await addPropertiesRelations(record, payload.data.properties);
				}

				if ('controls' in payload.data && Array.isArray(payload.data.controls)) {
					await addControlsRelations(record, payload.data.controls);
				}

				return (this.data[record.id] = record);
			}

			const record = await storeRecordFactory(payload.data);

			if ('properties' in payload.data && Array.isArray(payload.data.properties)) {
				await addPropertiesRelations(record, payload.data.properties);
			}

			if ('controls' in payload.data && Array.isArray(payload.data.controls)) {
				await addControlsRelations(record, payload.data.controls);
			}

			await addRecord<IChannelDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CHANNELS);

			this.meta[record.id] = record.type;

			this.data = this.data ?? {};
			return (this.data[record.id] = record);
		},

		/**
		 * Remove records for given relation or record by given identifier
		 *
		 * @param {IChannelsUnsetActionPayload} payload
		 */
		async unset(payload: IChannelsUnsetActionPayload): Promise<void> {
			if (!this.data) {
				return;
			}

			if (payload.device !== undefined) {
				const items = this.findForDevice(payload.device.id);

				for (const item of items) {
					if (item.id in (this.data ?? {})) {
						await removeRecord(item.id, DB_TABLE_CHANNELS);

						delete this.meta[item.id];

						delete (this.data ?? {})[item.id];
					}
				}

				return;
			} else if (payload.id !== undefined) {
				await removeRecord(payload.id, DB_TABLE_CHANNELS);

				delete this.meta[payload.id];

				delete this.data[payload.id];

				return;
			}

			throw new Error('You have to provide at least device or channel id');
		},

		/**
		 * Get one record from server
		 *
		 * @param {IChannelsGetActionPayload} payload
		 */
		async get(payload: IChannelsGetActionPayload): Promise<boolean> {
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
				let channelResponse: AxiosResponse<IChannelResponseJson>;

				if (payload.deviceId) {
					channelResponse = await axios.get<IChannelResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.deviceId}/channels/${payload.id}?include=properties,controls`
					);
				} else {
					channelResponse = await axios.get<IChannelResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/channels/${payload.id}?include=properties,controls`
					);
				}

				const channelResponseModel = jsonApiFormatter.deserialize(channelResponse.data) as IChannelResponseModel;

				this.data = this.data ?? {};
				this.data[channelResponseModel.id] = await storeRecordFactory({
					...channelResponseModel,
					...{ deviceId: channelResponseModel.device.id },
				});

				await addRecord<IChannelDatabaseRecord>(databaseRecordFactory(this.data[channelResponseModel.id]), DB_TABLE_CHANNELS);

				this.meta[channelResponseModel.id] = channelResponseModel.type;

				await addPropertiesRelations(this.data[channelResponseModel.id], channelResponseModel.properties);
				await addControlsRelations(this.data[channelResponseModel.id], channelResponseModel.controls);
			} catch (e: any) {
				throw new ApiError('devices-module.channels.get.failed', e, 'Fetching channel failed.');
			} finally {
				if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
					this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
				}
			}

			return true;
		},

		/**
		 * Fetch all records from server
		 *
		 * @param {IChannelsFetchActionPayload} payload
		 */
		async fetch(payload?: IChannelsFetchActionPayload): Promise<boolean> {
			if (this.semaphore.fetching.items.includes(payload?.deviceId ?? 'all')) {
				return false;
			}

			const fromDatabase = await this.loadAllRecords({ deviceId: payload?.deviceId });

			if (fromDatabase && payload?.refresh === false) {
				return true;
			}

			if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
				this.semaphore.fetching.items.push(payload?.deviceId ?? 'all');
			}

			this.firstLoad = this.firstLoad.filter((item) => item !== (payload?.deviceId ?? 'all'));
			this.firstLoad = [...new Set(this.firstLoad)];

			try {
				let channelsResponse: AxiosResponse<IChannelsResponseJson>;

				if (payload?.deviceId) {
					channelsResponse = await axios.get<IChannelsResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.deviceId}/channels?include=properties,controls`
					);
				} else {
					channelsResponse = await axios.get<IChannelsResponseJson>(`/${ModulePrefix.MODULE_DEVICES}/v1/channels?include=properties,controls`);
				}

				const channelsResponseModel = jsonApiFormatter.deserialize(channelsResponse.data) as IChannelResponseModel[];

				const deviceIds: string[] = [];

				for (const channel of channelsResponseModel) {
					this.data = this.data ?? {};
					this.data[channel.id] = await storeRecordFactory({
						...channel,
						...{ deviceId: channel.device.id },
					});

					deviceIds.push(channel.device.id);

					await addRecord<IChannelDatabaseRecord>(databaseRecordFactory(this.data[channel.id]), DB_TABLE_CHANNELS);

					this.meta[channel.id] = channel.type;

					await addPropertiesRelations(this.data[channel.id], channel.properties);
					await addControlsRelations(this.data[channel.id], channel.controls);
				}

				if (payload && payload.deviceId) {
					this.firstLoad.push(payload.deviceId);
					this.firstLoad = [...new Set(this.firstLoad)];

					// Get all current IDs from IndexedDB
					const allRecords = await getAllRecords<IChannelDatabaseRecord>(DB_TABLE_CHANNELS);
					const indexedDbIds: string[] = allRecords.filter((record) => record.device.id === payload.deviceId).map((record) => record.id);

					// Get the IDs from the latest changes
					const serverIds: string[] = Object.keys(this.data ?? {});

					// Find IDs that are in IndexedDB but not in the server response
					const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

					// Remove records that are no longer present on the server
					for (const id of idsToRemove) {
						await removeRecord(id, DB_TABLE_CHANNELS);

						delete this.meta[id];
					}
				} else {
					this.firstLoad.push('all');
					this.firstLoad = [...new Set(this.firstLoad)];

					const uniqueDeviceIds = [...new Set(deviceIds)];

					for (const deviceId of uniqueDeviceIds) {
						this.firstLoad.push(deviceId);
						this.firstLoad = [...new Set(this.firstLoad)];
					}

					// Get all current IDs from IndexedDB
					const allRecords = await getAllRecords<IChannelDatabaseRecord>(DB_TABLE_CHANNELS);
					const indexedDbIds: string[] = allRecords.map((record) => record.id);

					// Get the IDs from the latest changes
					const serverIds: string[] = Object.keys(this.data ?? {});

					// Find IDs that are in IndexedDB but not in the server response
					const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

					// Remove records that are no longer present on the server
					for (const id of idsToRemove) {
						await removeRecord(id, DB_TABLE_CHANNELS);

						delete this.meta[id];
					}
				}
			} catch (e: any) {
				throw new ApiError('devices-module.channels.fetch.failed', e, 'Fetching channels failed.');
			} finally {
				if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
					this.semaphore.fetching.items = this.semaphore.fetching.items.filter((item) => item !== (payload?.deviceId ?? 'all'));
				}
			}

			return true;
		},

		/**
		 * Add new record
		 *
		 * @param {IChannelsAddActionPayload} payload
		 */
		async add(payload: IChannelsAddActionPayload): Promise<IChannel> {
			const newChannel = await storeRecordFactory({
				...payload.data,
				...{
					id: payload?.id,
					type: payload?.type,
					category: ChannelCategory.GENERIC,
					draft: payload?.draft,
					deviceId: payload.device.id,
				},
			});

			this.semaphore.creating.push(newChannel.id);

			this.data = this.data ?? {};
			this.data[newChannel.id] = newChannel;

			if (newChannel.draft) {
				this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newChannel.id);

				return newChannel;
			} else {
				try {
					const createdChannel = await axios.post<IChannelResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/channels?include=properties,controls`,
						jsonApiFormatter.serialize({
							stuff: newChannel,
						})
					);

					const createdChannelModel = jsonApiFormatter.deserialize(createdChannel.data) as IChannelResponseModel;

					this.data[createdChannelModel.id] = await storeRecordFactory({
						...createdChannelModel,
						...{ deviceId: createdChannelModel.device.id },
					});

					await addRecord<IChannelDatabaseRecord>(databaseRecordFactory(this.data[createdChannelModel.id]), DB_TABLE_CHANNELS);

					this.meta[createdChannelModel.id] = createdChannelModel.type;

					await addPropertiesRelations(this.data[createdChannelModel.id], createdChannelModel.properties);
					await addControlsRelations(this.data[createdChannelModel.id], createdChannelModel.controls);

					return this.data[createdChannelModel.id];
				} catch (e: any) {
					// Transformer could not be created on api, we have to remove it from database
					delete this.data[newChannel.id];

					throw new ApiError('devices-module.channels.create.failed', e, 'Create new channel failed.');
				} finally {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newChannel.id);
				}
			}
		},

		/**
		 * Edit existing record
		 *
		 * @param {IChannelsEditActionPayload} payload
		 */
		async edit(payload: IChannelsEditActionPayload): Promise<IChannel> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('devices-module.channels.update.inProgress');
			}

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				throw new Error('devices-module.channels.update.failed');
			}

			this.semaphore.updating.push(payload.id);

			// Get record stored in database
			const existingRecord = this.data[payload.id];
			// Update with new values
			const updatedRecord = { ...existingRecord, ...payload.data } as IChannel;

			this.data[payload.id] = updatedRecord;

			if (updatedRecord.draft) {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

				return this.data[payload.id];
			} else {
				try {
					const updatedChannel = await axios.patch<IChannelResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${updatedRecord.device.id}/channels/${updatedRecord.id}?include=properties,controls`,
						jsonApiFormatter.serialize({
							stuff: updatedRecord,
						})
					);

					const updatedChannelModel = jsonApiFormatter.deserialize(updatedChannel.data) as IChannelResponseModel;

					this.data[updatedChannelModel.id] = await storeRecordFactory({
						...updatedChannelModel,
						...{ deviceId: updatedChannelModel.device.id },
					});

					await addRecord<IChannelDatabaseRecord>(databaseRecordFactory(this.data[updatedChannelModel.id]), DB_TABLE_CHANNELS);

					this.meta[updatedChannelModel.id] = updatedChannelModel.type;

					await addPropertiesRelations(this.data[updatedChannelModel.id], updatedChannelModel.properties);
					await addControlsRelations(this.data[updatedChannelModel.id], updatedChannelModel.controls);

					return this.data[updatedChannelModel.id];
				} catch (e: any) {
					const devicesStore = useDevices();

					const device = devicesStore.findById(updatedRecord.device.id);

					if (device !== null) {
						// Updating entity on api failed, we need to refresh entity
						await this.get({ deviceId: device.id, id: payload.id });
					}

					throw new ApiError('devices-module.channels.update.failed', e, 'Edit channel failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}
			}
		},

		/**
		 * Save draft record on server
		 *
		 * @param {IChannelsSaveActionPayload} payload
		 */
		async save(payload: IChannelsSaveActionPayload): Promise<IChannel> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('devices-module.channels.save.inProgress');
			}

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				throw new Error('devices-module.channels.save.failed');
			}

			this.semaphore.updating.push(payload.id);

			const recordToSave = this.data[payload.id];

			try {
				const savedChannel = await axios.post<IChannelResponseJson>(
					`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${recordToSave.device.id}/channels?include=properties,controls`,
					jsonApiFormatter.serialize({
						stuff: recordToSave,
					})
				);

				const savedChannelModel = jsonApiFormatter.deserialize(savedChannel.data) as IChannelResponseModel;

				this.data[savedChannelModel.id] = await storeRecordFactory({
					...savedChannelModel,
					...{ deviceId: savedChannelModel.device.id },
				});

				await addRecord<IChannelDatabaseRecord>(databaseRecordFactory(this.data[savedChannelModel.id]), DB_TABLE_CHANNELS);

				this.meta[savedChannelModel.id] = savedChannelModel.type;

				await addPropertiesRelations(this.data[savedChannelModel.id], savedChannelModel.properties);
				await addControlsRelations(this.data[savedChannelModel.id], savedChannelModel.controls);

				return this.data[savedChannelModel.id];
			} catch (e: any) {
				throw new ApiError('devices-module.channels.save.failed', e, 'Save draft channel failed.');
			} finally {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
			}
		},

		/**
		 * Remove existing record from store and server
		 *
		 * @param {IChannelsRemoveActionPayload} payload
		 */
		async remove(payload: IChannelsRemoveActionPayload): Promise<boolean> {
			if (this.semaphore.deleting.includes(payload.id)) {
				throw new Error('devices-module.channels.delete.inProgress');
			}

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				throw new Error('devices-module.channels.delete.failed');
			}

			this.semaphore.deleting.push(payload.id);

			const recordToDelete = this.data[payload.id];

			delete this.data[payload.id];

			await removeRecord(payload.id, DB_TABLE_CHANNELS);

			delete this.meta[payload.id];

			if (recordToDelete.draft) {
				this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
			} else {
				try {
					await axios.delete(`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${recordToDelete.device.id}/channels/${recordToDelete.id}`);
				} catch (e: any) {
					const devicesStore = useDevices();

					const device = devicesStore.findById(recordToDelete.device.id);

					if (device !== null) {
						// Deleting entity on api failed, we need to refresh entity
						await this.get({ deviceId: device.id, id: payload.id });
					}

					throw new ApiError('devices-module.channels.delete.failed', e, 'Delete channel failed.');
				} finally {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				}
			}

			return true;
		},

		/**
		 * Receive data from sockets
		 *
		 * @param {IChannelsSocketDataActionPayload} payload
		 */
		async socketData(payload: IChannelsSocketDataActionPayload): Promise<boolean> {
			if (
				![
					RoutingKeys.CHANNEL_DOCUMENT_REPORTED,
					RoutingKeys.CHANNEL_DOCUMENT_CREATED,
					RoutingKeys.CHANNEL_DOCUMENT_UPDATED,
					RoutingKeys.CHANNEL_DOCUMENT_DELETED,
				].includes(payload.routingKey as RoutingKeys)
			) {
				return false;
			}

			const body: ChannelDocument = JSON.parse(payload.data);

			const isValid = jsonSchemaValidator.compile<ChannelDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(body)) {
					return false;
				}
			} catch {
				return false;
			}

			if (payload.routingKey === RoutingKeys.CHANNEL_DOCUMENT_DELETED) {
				await removeRecord(body.id, DB_TABLE_CHANNELS);

				delete this.meta[body.id];

				if (this.data && body.id in this.data) {
					const recordToDelete = this.data[body.id];

					delete this.data[body.id];

					const channelPropertiesStore = useChannelProperties();
					const channelControlsStore = useChannelControls();

					channelPropertiesStore.unset({ channel: recordToDelete });
					channelControlsStore.unset({ channel: recordToDelete });
				}
			} else {
				if (payload.routingKey === RoutingKeys.CHANNEL_DOCUMENT_UPDATED && this.semaphore.updating.includes(body.id)) {
					return true;
				}

				if (this.data && body.id in this.data) {
					const record = await storeRecordFactory({
						...this.data[body.id],
						...{
							category: body.category,
							name: body.name,
							comment: body.comment,
							deviceId: body.device,
						},
					});

					if (!isEqual(JSON.parse(JSON.stringify(this.data[body.id])), JSON.parse(JSON.stringify(record)))) {
						this.data[body.id] = record;

						await addRecord<IChannelDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CHANNELS);

						this.meta[record.id] = record.type;
					}
				} else {
					const devicesStore = useDevices();

					const device = devicesStore.findById(body.device);

					if (device !== null) {
						try {
							await this.get({
								deviceId: device.id,
								id: body.id,
							});
						} catch {
							return false;
						}
					} else {
						try {
							const channelsStore = useChannels();

							await devicesStore.get({ id: body.device });

							const device = devicesStore.findById(body.device);

							if (device === null) {
								return false;
							}

							await channelsStore.fetch({ deviceId: device.id });
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
		 * @param {IChannelsInsertDataActionPayload} payload
		 */
		async insertData(payload: IChannelsInsertDataActionPayload): Promise<boolean> {
			this.data = this.data ?? {};

			let documents: ChannelDocument[] = [];

			if (Array.isArray(payload.data)) {
				documents = payload.data;
			} else {
				documents = [payload.data];
			}

			const deviceIds = [];

			for (const doc of documents) {
				const isValid = jsonSchemaValidator.compile<ChannelDocument>(exchangeDocumentSchema);

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
							entity: 'channel',
						},
						category: doc.category,
						identifier: doc.identifier,
						name: doc.name,
						comment: doc.comment,
						deviceId: doc.device,
					},
				});

				if (documents.length === 1) {
					this.data[doc.id] = record;
				}

				await addRecord<IChannelDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CHANNELS);

				this.meta[record.id] = record.type;

				deviceIds.push(doc.device);
			}

			if (documents.length > 1) {
				const uniqueDeviceIds = [...new Set(deviceIds)];

				if (uniqueDeviceIds.length > 1) {
					this.firstLoad.push('all');
					this.firstLoad = [...new Set(this.firstLoad)];
				}

				for (const deviceId of uniqueDeviceIds) {
					this.firstLoad.push(deviceId);
					this.firstLoad = [...new Set(this.firstLoad)];
				}
			}

			return true;
		},

		/**
		 * Load record from database
		 *
		 * @param {IChannelsLoadRecordActionPayload} payload
		 */
		async loadRecord(payload: IChannelsLoadRecordActionPayload): Promise<boolean> {
			const record = await getRecord<IChannelDatabaseRecord>(payload.id, DB_TABLE_CHANNELS);

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
		 * @param {IChannelsLoadAllRecordsActionPayload} payload
		 */
		async loadAllRecords(payload?: IChannelsLoadAllRecordsActionPayload): Promise<boolean> {
			const records = await getAllRecords<IChannelDatabaseRecord>(DB_TABLE_CHANNELS);

			this.data = this.data ?? {};

			for (const record of records) {
				if (payload?.deviceId && payload?.deviceId !== record?.device.id) {
					continue;
				}

				this.data[record.id] = await storeRecordFactory(record);
			}

			return true;
		},
	},
});
