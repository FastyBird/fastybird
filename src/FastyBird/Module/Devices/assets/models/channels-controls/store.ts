import { defineStore } from 'pinia';
import axios from 'axios';
import { Jsona } from 'jsona';
import Ajv from 'ajv/dist/2020';
import { v4 as uuid } from 'uuid';
import get from 'lodash/get';
import isEqual from 'lodash/isEqual';

import exchangeEntitySchema from '@fastybird/metadata-library/resources/schemas/modules/devices-module/entity.channel.control.json';
import {
	ActionRoutes,
	ChannelControlEntity as ExchangeEntity,
	ControlAction,
	DevicesModuleRoutes as RoutingKeys,
	ModulePrefix,
} from '@fastybird/metadata-library';

import { ApiError } from '@/errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '@/jsonapi';
import { useChannels, useDevices } from '@/models';
import { IChannel } from '@/models/types';

import {
	IChannelControlsState,
	IChannelControlsActions,
	IChannelControlsGetters,
	IChannelControl,
	IChannelControlsAddActionPayload,
	IChannelControlRecordFactoryPayload,
	IChannelControlResponseModel,
	IChannelControlResponseJson,
	IChannelControlsResponseJson,
	IChannelControlsGetActionPayload,
	IChannelControlsFetchActionPayload,
	IChannelControlsSaveActionPayload,
	IChannelControlsRemoveActionPayload,
	IChannelControlsSocketDataActionPayload,
	IChannelControlsUnsetActionPayload,
	IChannelControlsSetActionPayload,
	IChannelControlsTransmitCommandActionPayload,
} from './types';
import { useWsExchangeClient } from '@fastybird/ws-exchange-plugin';

const jsonSchemaValidator = new Ajv();

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const recordFactory = async (data: IChannelControlRecordFactoryPayload): Promise<IChannelControl> => {
	const channelsStore = useChannels();

	const channel = channelsStore.findById(data.channelId);

	if (channel === null) {
		throw new Error("Channel for control couldn't be loaded from store");
	}

	return {
		id: get(data, 'id', uuid().toString()),
		type: { ...{ parent: 'channel', entity: 'control' }, ...data.type },

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

				firstLoad: [],

				data: {},
			};
		},

		getters: {
			firstLoadFinished: (state: IChannelControlsState): ((channelId: string) => boolean) => {
				return (channelId) => state.firstLoad.includes(channelId);
			},

			getting: (state: IChannelControlsState): ((controlId: string) => boolean) => {
				return (controlId) => state.semaphore.fetching.item.includes(controlId);
			},

			fetching: (state: IChannelControlsState): ((channelId: string | null) => boolean) => {
				return (channelId) => (channelId !== null ? state.semaphore.fetching.items.includes(channelId) : state.semaphore.fetching.items.length > 0);
			},

			findById: (state: IChannelControlsState): ((id: string) => IChannelControl | null) => {
				return (id) => {
					const control = Object.values(state.data).find((control) => control.id === id);

					return control ?? null;
				};
			},

			findByName: (state: IChannelControlsState): ((channel: IChannel, name: string) => IChannelControl | null) => {
				return (channel: IChannel, name) => {
					const control = Object.values(state.data).find((control) => {
						return control.channel.id === channel.id && control.name.toLowerCase() === name.toLowerCase();
					});

					return control ?? null;
				};
			},

			findForChannel: (state: IChannelControlsState): ((channelId: string) => IChannelControl[]) => {
				return (channelId: string): IChannelControl[] => {
					return Object.values(state.data).filter((control) => control.channel.id === channelId);
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
			 * @param {IChannelControlsUnsetActionPayload} payload
			 */
			unset(payload: IChannelControlsUnsetActionPayload): void {
				if (typeof payload.channel !== 'undefined') {
					Object.keys(this.data).forEach((id) => {
						if (id in this.data && this.data[id].channel.id === payload.channel?.id) {
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

				const channelsStore = useChannels();

				const channel = channelsStore.findById(payload.channel.id);

				if (channel === null) {
					throw new Error('devices-module.channel-controls.get.failed');
				}

				this.semaphore.fetching.item.push(payload.id);

				try {
					const controlResponse = await axios.get<IChannelControlResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${channel.device.id}/channels/${payload.channel.id}/controls/${payload.id}`
					);

					const controlResponseModel = jsonApiFormatter.deserialize(controlResponse.data) as IChannelControlResponseModel;

					this.data[controlResponseModel.id] = await recordFactory({
						...controlResponseModel,
						...{ channelId: controlResponseModel.channel.id },
					});
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

				const channelsStore = useChannels();

				const channel = channelsStore.findById(payload.channel.id);

				if (channel === null) {
					throw new Error('devices-module.channel-controls.get.failed');
				}

				this.semaphore.fetching.items.push(payload.channel.id);

				try {
					const controlsResponse = await axios.get<IChannelControlsResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${channel.device.id}/channels/${payload.channel.id}/controls`
					);

					const controlsResponseModel = jsonApiFormatter.deserialize(controlsResponse.data) as IChannelControlResponseModel[];

					for (const control of controlsResponseModel) {
						this.data[control.id] = await recordFactory({
							...control,
							...{ channelId: control.channel.id },
						});
					}

					this.firstLoad.push(payload.channel.id);
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
				const newControl = await recordFactory({
					...{
						id: payload?.id,
						type: payload?.type,
						draft: payload?.draft,
						channelId: payload.channel.id,
					},
					...payload.data,
				});

				this.semaphore.creating.push(newControl.id);

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
							`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${channel.device.id}/channels/${payload.channel.id}/controls`,
							jsonApiFormatter.serialize({
								stuff: newControl,
							})
						);

						const createdControlModel = jsonApiFormatter.deserialize(createdControl.data) as IChannelControlResponseModel;

						this.data[createdControlModel.id] = await recordFactory({
							...createdControlModel,
							...{ channelId: createdControlModel.channel.id },
						});

						return this.data[createdControlModel.id];
					} catch (e: any) {
						// Transformer could not be created on api, we have to remove it from database
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

				if (!Object.keys(this.data).includes(payload.id)) {
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
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${channel.device.id}/channels/${recordToSave.channel.id}/controls`,
						jsonApiFormatter.serialize({
							stuff: recordToSave,
						})
					);

					const savedControlModel = jsonApiFormatter.deserialize(savedControl.data) as IChannelControlResponseModel;

					this.data[savedControlModel.id] = await recordFactory({
						...savedControlModel,
						...{ channelId: savedControlModel.channel.id },
					});

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

				if (!Object.keys(this.data).includes(payload.id)) {
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

				if (recordToDelete.draft) {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				} else {
					try {
						await axios.delete(
							`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${channel.device.id}/channels/${recordToDelete.channel.id}/controls/${recordToDelete.id}`
						);
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
				if (!Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.channel-controls.transmit.failed');
				}

				const control = this.data[payload.id];

				const channelsStore = useChannels();

				const channel = channelsStore.findById(control.channel.id);

				if (channel === null) {
					throw new Error('devices-module.channel-controls.transmit.failed');
				}

				const devicesStore = useDevices();

				const device = devicesStore.findById(channel.device.id);

				if (device === null) {
					throw new Error('devices-module.channel-controls.transmit.failed');
				}

				const { call } = useWsExchangeClient<{ data: string }>();

				try {
					const response = await call('', {
						routing_key: ActionRoutes.CHANNEL_CONTROL,
						source: control.type.source,
						data: {
							action: ControlAction.SET,
							device: device.id,
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
						RoutingKeys.CONNECTOR_CONTROL_ENTITY_REPORTED,
						RoutingKeys.CONNECTOR_CONTROL_ENTITY_CREATED,
						RoutingKeys.CONNECTOR_CONTROL_ENTITY_UPDATED,
						RoutingKeys.CONNECTOR_CONTROL_ENTITY_DELETED,
					].includes(payload.routingKey as RoutingKeys)
				) {
					return false;
				}

				const body: ExchangeEntity = JSON.parse(payload.data);

				const isValid = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema);

				try {
					if (!isValid(body)) {
						return false;
					}
				} catch {
					return false;
				}

				if (payload.routingKey === RoutingKeys.CONNECTOR_CONTROL_ENTITY_DELETED) {
					if (body.id in this.data) {
						delete this.data[body.id];
					}
				} else {
					if (body.id in this.data) {
						const record = await recordFactory({
							...this.data[body.id],
							...{
								name: body.name,
								channelId: body.channel,
							},
						});

						if (!isEqual(JSON.parse(JSON.stringify(this.data[body.id])), JSON.parse(JSON.stringify(record)))) {
							this.data[body.id] = record;
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
		},
	}
);
