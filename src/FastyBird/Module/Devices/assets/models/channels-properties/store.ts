import { defineStore } from 'pinia';
import axios from 'axios';
import { Jsona } from 'jsona';
import Ajv from 'ajv/dist/2020';
import { v4 as uuid } from 'uuid';
import get from 'lodash.get';
import isEqual from 'lodash.isEqual';

import exchangeDocumentSchema from '../../../resources/schemas/document.channel.property.json';
import {
	ChannelPropertyDocument,
	DevicesModuleRoutes as RoutingKeys,
	ModulePrefix,
	PropertyCategory,
	PropertyType,
} from '@fastybird/metadata-library';

import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { useChannels } from '../../models';
import { IChannel, IChannelPropertiesSetStateActionPayload, IPlainRelation } from '../../models/types';

import {
	IChannelPropertiesState,
	IChannelPropertiesActions,
	IChannelPropertiesGetters,
	IChannelPropertiesAddActionPayload,
	IChannelPropertiesEditActionPayload,
	IChannelPropertiesFetchActionPayload,
	IChannelPropertiesGetActionPayload,
	IChannelPropertiesRemoveActionPayload,
	IChannelPropertiesResponseJson,
	IChannelPropertiesSaveActionPayload,
	IChannelPropertiesSetActionPayload,
	IChannelPropertiesSocketDataActionPayload,
	IChannelPropertiesUnsetActionPayload,
	IChannelProperty,
	IChannelPropertyRecordFactoryPayload,
	IChannelPropertyResponseJson,
	IChannelPropertyResponseModel,
} from './types';

const jsonSchemaValidator = new Ajv();

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const recordFactory = async (data: IChannelPropertyRecordFactoryPayload): Promise<IChannelProperty> => {
	const channelsStore = useChannels();

	const channel = channelsStore.findById(data.channelId);

	if (channel === null) {
		throw new Error("Channel for property couldn't be loaded from store");
	}

	const record: IChannelProperty = {
		id: get(data, 'id', uuid().toString()),
		type: { ...{ parent: 'channel', entity: 'property' }, ...data.type },

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
		relationshipNames: ['channel', 'parent', 'children'],

		channel: {
			id: channel.id,
			type: channel.type,
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

export const useChannelProperties = defineStore<string, IChannelPropertiesState, IChannelPropertiesGetters, IChannelPropertiesActions>(
	'devices_module_channels_properties',
	{
		state: (): IChannelPropertiesState => {
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
			firstLoadFinished: (state: IChannelPropertiesState): ((channelId: string) => boolean) => {
				return (channelId) => state.firstLoad.includes(channelId);
			},

			getting: (state: IChannelPropertiesState): ((propertyId: string) => boolean) => {
				return (propertyId) => state.semaphore.fetching.item.includes(propertyId);
			},

			fetching: (state: IChannelPropertiesState): ((channelId: string | null) => boolean) => {
				return (channelId) => (channelId !== null ? state.semaphore.fetching.items.includes(channelId) : state.semaphore.fetching.items.length > 0);
			},

			findById: (state: IChannelPropertiesState): ((id: string) => IChannelProperty | null) => {
				return (id) => {
					const property = Object.values(state.data).find((property) => property.id === id);

					return property ?? null;
				};
			},

			findByIdentifier: (state: IChannelPropertiesState): ((channel: IChannel, identifier: string) => IChannelProperty | null) => {
				return (channel: IChannel, identifier) => {
					const property = Object.values(state.data).find((property) => {
						return property.channel.id === channel.id && property.identifier.toLowerCase() === identifier.toLowerCase();
					});

					return property ?? null;
				};
			},

			findForChannel: (state: IChannelPropertiesState): ((channelId: string) => IChannelProperty[]) => {
				return (channelId: string): IChannelProperty[] => {
					return Object.values(state.data).filter((property) => property.channel.id === channelId);
				};
			},
		},

		actions: {
			/**
			 * Set record from via other store
			 *
			 * @param {IChannelPropertiesSetActionPayload} payload
			 */
			async set(payload: IChannelPropertiesSetActionPayload): Promise<IChannelProperty> {
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
			 * @param {IChannelPropertiesUnsetActionPayload} payload
			 */
			unset(payload: IChannelPropertiesUnsetActionPayload): void {
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

				throw new Error('You have to provide at least channel or property id');
			},

			/**
			 * Get one record from server
			 *
			 * @param {IChannelPropertiesGetActionPayload} payload
			 */
			async get(payload: IChannelPropertiesGetActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.item.includes(payload.id)) {
					return false;
				}

				const channelsStore = useChannels();

				const channel = channelsStore.findById(payload.channel.id);

				if (channel === null) {
					throw new Error('devices-module.channel-properties.get.failed');
				}

				this.semaphore.fetching.item.push(payload.id);

				try {
					const propertyResponse = await axios.get<IChannelPropertyResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${channel.device.id}/channels/${payload.channel.id}/properties/${payload.id}`
					);

					const propertyResponseModel = jsonApiFormatter.deserialize(propertyResponse.data) as IChannelPropertyResponseModel;

					this.data[propertyResponseModel.id] = await recordFactory({
						...propertyResponseModel,
						...{ channelId: propertyResponseModel.channel.id, parentId: propertyResponseModel.parent?.id },
					});
				} catch (e: any) {
					throw new ApiError('devices-module.channel-properties.get.failed', e, 'Fetching property failed.');
				} finally {
					this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
				}

				return true;
			},

			/**
			 * Fetch all records from server
			 *
			 * @param {IChannelPropertiesFetchActionPayload} payload
			 */
			async fetch(payload: IChannelPropertiesFetchActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.items.includes(payload.channel.id)) {
					return false;
				}

				const channelsStore = useChannels();

				const channel = channelsStore.findById(payload.channel.id);

				if (channel === null) {
					throw new Error('devices-module.channel-properties.fetch.failed');
				}

				this.semaphore.fetching.items.push(payload.channel.id);

				try {
					const propertiesResponse = await axios.get<IChannelPropertiesResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${channel.device.id}/channels/${payload.channel.id}/properties`
					);

					const propertiesResponseModel = jsonApiFormatter.deserialize(propertiesResponse.data) as IChannelPropertyResponseModel[];

					for (const property of propertiesResponseModel) {
						this.data[property.id] = await recordFactory({
							...property,
							...{ channelId: property.channel.id, parentId: property.parent?.id },
						});
					}

					this.firstLoad.push(payload.channel.id);
				} catch (e: any) {
					throw new ApiError('devices-module.channel-properties.fetch.failed', e, 'Fetching properties failed.');
				} finally {
					this.semaphore.fetching.items = this.semaphore.fetching.items.filter((item) => item !== payload.channel.id);
				}

				return true;
			},

			/**
			 * Add new record
			 *
			 * @param {IChannelPropertiesAddActionPayload} payload
			 */
			async add(payload: IChannelPropertiesAddActionPayload): Promise<IChannelProperty> {
				const newProperty = await recordFactory({
					...{
						id: payload?.id,
						type: payload?.type,
						category: PropertyCategory.GENERIC,
						draft: payload?.draft,
						channelId: payload.channel.id,
					},
					...payload.data,
				});

				this.semaphore.creating.push(newProperty.id);

				this.data[newProperty.id] = newProperty;

				if (newProperty.draft) {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newProperty.id);

					return newProperty;
				} else {
					const channelsStore = useChannels();

					const channel = channelsStore.findById(payload.channel.id);

					if (channel === null) {
						this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newProperty.id);

						throw new Error('devices-module.channel-properties.create.failed');
					}

					try {
						const apiData: Partial<IChannelProperty> =
							newProperty.parent !== null
								? {
										id: newProperty.id,
										type: newProperty.type,
										identifier: newProperty.identifier,
										name: newProperty.name,
										value: newProperty.value,
										channel: newProperty.channel,
										parent: newProperty.parent,
										relationshipNames: ['channel', 'parent'],
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
										channel: newProperty.channel,
										relationshipNames: ['channel'],
									};

						if (apiData?.type?.type === PropertyType.DYNAMIC) {
							delete apiData.value;
						}

						if (apiData?.type?.type === PropertyType.VARIABLE) {
							delete apiData.settable;
							delete apiData.queryable;
						}

						const createdProperty = await axios.post<IChannelPropertyResponseJson>(
							`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${channel.device.id}/channels/${payload.channel.id}/properties`,
							jsonApiFormatter.serialize({
								stuff: apiData,
							})
						);

						const createdPropertyModel = jsonApiFormatter.deserialize(createdProperty.data) as IChannelPropertyResponseModel;

						this.data[createdPropertyModel.id] = await recordFactory({
							...createdPropertyModel,
							...{ channelId: createdPropertyModel.channel.id, parentId: createdPropertyModel.parent?.id },
						});

						return this.data[createdPropertyModel.id];
					} catch (e: any) {
						// Transformer could not be created on api, we have to remove it from database
						delete this.data[newProperty.id];

						throw new ApiError('devices-module.channel-properties.create.failed', e, 'Create new property failed.');
					} finally {
						this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newProperty.id);
					}
				}
			},

			/**
			 * Edit existing record
			 *
			 * @param {IChannelPropertiesEditActionPayload} payload
			 */
			async edit(payload: IChannelPropertiesEditActionPayload): Promise<IChannelProperty> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('devices-module.channel-properties.update.inProgress');
				}

				if (!Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.channel-properties.update.failed');
				}

				this.semaphore.updating.push(payload.id);

				// Get record stored in database
				const existingRecord = this.data[payload.id];
				// Update with new values
				const updatedRecord = {
					...existingRecord,
					...payload.data,
					...{ parent: payload.parent ? { id: payload.parent.id, type: payload.parent.type } : existingRecord.parent },
				} as IChannelProperty;

				this.data[payload.id] = updatedRecord;

				if (updatedRecord.draft) {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

					return this.data[payload.id];
				} else {
					const channelsStore = useChannels();

					const channel = channelsStore.findById(updatedRecord.channel.id);

					if (channel === null) {
						this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

						throw new Error('devices-module.channel-properties.update.failed');
					}

					try {
						const apiData: Partial<IChannelProperty> =
							updatedRecord.parent !== null
								? {
										id: updatedRecord.id,
										type: updatedRecord.type,
										identifier: updatedRecord.identifier,
										name: updatedRecord.name,
										value: updatedRecord.value,
										channel: updatedRecord.channel,
										parent: updatedRecord.parent,
										relationshipNames: ['channel', 'parent'],
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
										channel: updatedRecord.channel,
										relationshipNames: ['channel'],
									};

						if (apiData?.type?.type === PropertyType.DYNAMIC) {
							delete apiData.value;
						}

						if (apiData?.type?.type === PropertyType.VARIABLE) {
							delete apiData.settable;
							delete apiData.queryable;
						}

						const updatedProperty = await axios.patch<IChannelPropertyResponseJson>(
							`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${channel.device.id}/channels/${updatedRecord.channel.id}/properties/${updatedRecord.id}`,
							jsonApiFormatter.serialize({
								stuff: apiData,
							})
						);

						const updatedPropertyModel = jsonApiFormatter.deserialize(updatedProperty.data) as IChannelPropertyResponseModel;

						this.data[updatedPropertyModel.id] = await recordFactory({
							...updatedPropertyModel,
							...{ channelId: updatedPropertyModel.channel.id, parentId: updatedPropertyModel.parent?.id },
						});

						return this.data[updatedPropertyModel.id];
					} catch (e: any) {
						const channelsStore = useChannels();

						const channel = channelsStore.findById(updatedRecord.channel.id);

						if (channel !== null) {
							// Updating entity on api failed, we need to refresh entity
							await this.get({ channel, id: payload.id });
						}

						throw new ApiError('devices-module.channel-properties.update.failed', e, 'Edit property failed.');
					} finally {
						this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
					}
				}
			},

			/**
			 * Save property state record
			 *
			 * @param {IChannelPropertiesSetStateActionPayload} payload
			 */
			async setState(payload: IChannelPropertiesSetStateActionPayload): Promise<IChannelProperty> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('devices-module.channel-properties.update.inProgress');
				}

				if (!Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.channel-properties.update.failed');
				}

				this.semaphore.updating.push(payload.id);

				// Get record stored in database
				const existingRecord = this.data[payload.id];
				// Update with new values
				this.data[payload.id] = {
					...existingRecord,
					...payload.data,
					...{ parent: payload.parent ? { id: payload.parent.id, type: payload.parent.type } : existingRecord.parent },
				} as IChannelProperty;

				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

				return this.data[payload.id];
			},

			/**
			 * Save draft record on server
			 *
			 * @param {IChannelPropertiesSaveActionPayload} payload
			 */
			async save(payload: IChannelPropertiesSaveActionPayload): Promise<IChannelProperty> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('devices-module.channel-properties.save.inProgress');
				}

				if (!Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.channel-properties.save.failed');
				}

				this.semaphore.updating.push(payload.id);

				const recordToSave = this.data[payload.id];

				const channelsStore = useChannels();

				const channel = channelsStore.findById(recordToSave.channel.id);

				if (channel === null) {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

					throw new Error('devices-module.channel-properties.save.failed');
				}

				try {
					const apiData: Partial<IChannelProperty> =
						recordToSave.parent !== null
							? {
									id: recordToSave.id,
									type: recordToSave.type,
									identifier: recordToSave.identifier,
									name: recordToSave.name,
									value: recordToSave.value,
									channel: recordToSave.channel,
									parent: recordToSave.parent,
									relationshipNames: ['channel', 'parent'],
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
									channel: recordToSave.channel,
									relationshipNames: ['channel'],
								};

					if (apiData?.type?.type === PropertyType.DYNAMIC) {
						delete apiData.value;
					}

					if (apiData?.type?.type === PropertyType.VARIABLE) {
						delete apiData.settable;
						delete apiData.queryable;
					}

					const savedProperty = await axios.post<IChannelPropertyResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${channel.device.id}/channels/${recordToSave.channel.id}/properties`,
						jsonApiFormatter.serialize({
							stuff: apiData,
						})
					);

					const savedPropertyModel = jsonApiFormatter.deserialize(savedProperty.data) as IChannelPropertyResponseModel;

					this.data[savedPropertyModel.id] = await recordFactory({
						...savedPropertyModel,
						...{ channelId: savedPropertyModel.channel.id, parentId: savedPropertyModel.parent?.id },
					});

					return this.data[savedPropertyModel.id];
				} catch (e: any) {
					throw new ApiError('devices-module.channel-properties.save.failed', e, 'Save draft property failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}
			},

			/**
			 * Remove existing record from store and server
			 *
			 * @param {IChannelPropertiesRemoveActionPayload} payload
			 */
			async remove(payload: IChannelPropertiesRemoveActionPayload): Promise<boolean> {
				if (this.semaphore.deleting.includes(payload.id)) {
					throw new Error('devices-module.channel-properties.delete.inProgress');
				}

				if (!Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.channel-properties.delete.failed');
				}

				this.semaphore.deleting.push(payload.id);

				const recordToDelete = this.data[payload.id];

				const channelsStore = useChannels();

				const channel = channelsStore.findById(recordToDelete.channel.id);

				if (channel === null) {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);

					throw new Error('devices-module.channel-properties.delete.failed');
				}

				delete this.data[payload.id];

				if (recordToDelete.draft) {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				} else {
					try {
						await axios.delete(
							`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${channel.device.id}/channels/${recordToDelete.channel.id}/properties/${recordToDelete.id}`
						);
					} catch (e: any) {
						const channelsStore = useChannels();

						const channel = channelsStore.findById(recordToDelete.channel.id);

						if (channel !== null) {
							// Deleting entity on api failed, we need to refresh entity
							await this.get({ channel, id: payload.id });
						}

						throw new ApiError('devices-module.channel-properties.delete.failed', e, 'Delete property failed.');
					} finally {
						this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
					}
				}

				return true;
			},

			/**
			 * Receive data from sockets
			 *
			 * @param {IChannelPropertiesSocketDataActionPayload} payload
			 */
			async socketData(payload: IChannelPropertiesSocketDataActionPayload): Promise<boolean> {
				if (
					![
						RoutingKeys.CHANNEL_PROPERTY_DOCUMENT_REPORTED,
						RoutingKeys.CHANNEL_PROPERTY_DOCUMENT_CREATED,
						RoutingKeys.CHANNEL_PROPERTY_DOCUMENT_UPDATED,
						RoutingKeys.CHANNEL_PROPERTY_DOCUMENT_DELETED,
					].includes(payload.routingKey as RoutingKeys)
				) {
					return false;
				}

				const body: ChannelPropertyDocument = JSON.parse(payload.data);

				const isValid = jsonSchemaValidator.compile<ChannelPropertyDocument>(exchangeDocumentSchema);

				try {
					if (!isValid(body)) {
						return false;
					}
				} catch {
					return false;
				}

				if (payload.routingKey === RoutingKeys.CHANNEL_PROPERTY_DOCUMENT_DELETED) {
					if (body.id in this.data) {
						delete this.data[body.id];
					}
				} else {
					if (payload.routingKey === RoutingKeys.CHANNEL_PROPERTY_DOCUMENT_UPDATED && this.semaphore.updating.includes(body.id)) {
						return true;
					}

					if (body.id in this.data) {
						const record = await recordFactory({
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
