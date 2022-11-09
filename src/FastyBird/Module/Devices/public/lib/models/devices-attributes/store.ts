import { defineStore } from 'pinia';
import axios from 'axios';
import JsonaService from 'jsona';
import Ajv from 'ajv';
import { v4 as uuid } from 'uuid';
import get from 'lodash/get';

import exchangeEntitySchema from '@fastybird/metadata-library/resources/schemas/modules/devices-module/entity.device.attribute.json';
import { DeviceAttributeEntity as ExchangeEntity, DevicesModuleRoutes as RoutingKeys, ModulePrefix } from '@fastybird/metadata-library';

import { ApiError } from '@/lib/errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '@/lib/jsonapi';
import { useDevices } from '@/lib/models';
import { IDevice } from '@/lib/models/types';

import {
	IDeviceAttributesState,
	IDeviceAttributesActions,
	IDeviceAttributesGetters,
	IDeviceAttribute,
	IDeviceAttributesAddActionPayload,
	IDeviceAttributeRecordFactoryPayload,
	IDeviceAttributeResponseModel,
	IDeviceAttributeResponseJson,
	IDeviceAttributesResponseJson,
	IDeviceAttributesGetActionPayload,
	IDeviceAttributesFetchActionPayload,
	IDeviceAttributesSaveActionPayload,
	IDeviceAttributesRemoveActionPayload,
	IDeviceAttributesSocketDataActionPayload,
	IDeviceAttributesUnsetActionPayload,
	IDeviceAttributesSetActionPayload,
} from './types';

const jsonSchemaValidator = new Ajv();

const jsonApiFormatter = new JsonaService({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const recordFactory = async (data: IDeviceAttributeRecordFactoryPayload): Promise<IDeviceAttribute> => {
	const devicesStore = useDevices();

	let device = devicesStore.findById(data.deviceId);

	if (device === null) {
		if (!(await devicesStore.get({ id: data.deviceId }))) {
			throw new Error("Device for attribute couldn't be loaded from server");
		}

		device = devicesStore.findById(data.deviceId);

		if (device === null) {
			throw new Error("Device for attribute couldn't be loaded from store");
		}
	}

	return {
		id: get(data, 'id', uuid().toString()),
		type: { ...{ parent: 'device', entity: 'attribute' }, ...data.type },

		draft: get(data, 'draft', false),

		identifier: data.identifier,
		name: get(data, 'name', null),
		content: get(data, 'content', null),

		// Relations
		relationshipNames: ['device'],

		device: {
			id: device.id,
			type: device.type,
		},
	} as IDeviceAttribute;
};

export const useDeviceAttributes = defineStore<string, IDeviceAttributesState, IDeviceAttributesGetters, IDeviceAttributesActions>(
	'devices_module_devices_attributes',
	{
		state: (): IDeviceAttributesState => {
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
			firstLoadFinished: (state: IDeviceAttributesState): ((deviceId: string) => boolean) => {
				return (deviceId) => state.firstLoad.includes(deviceId);
			},

			getting: (state: IDeviceAttributesState): ((attributeId: string) => boolean) => {
				return (attributeId) => state.semaphore.fetching.item.includes(attributeId);
			},

			fetching: (state: IDeviceAttributesState): ((deviceId: string | null) => boolean) => {
				return (deviceId) => (deviceId !== null ? state.semaphore.fetching.items.includes(deviceId) : state.semaphore.fetching.items.length > 0);
			},

			findById: (state: IDeviceAttributesState): ((id: string) => IDeviceAttribute | null) => {
				return (id) => {
					const attribute = Object.values(state.data).find((attribute) => attribute.id === id);

					return attribute ?? null;
				};
			},

			findByIdentifier: (state: IDeviceAttributesState): ((device: IDevice, identifier: string) => IDeviceAttribute | null) => {
				return (device: IDevice, identifier) => {
					const attribute = Object.values(state.data).find((attribute) => {
						return attribute.device.id === device.id && attribute.identifier.toLowerCase() === identifier.toLowerCase();
					});

					return attribute ?? null;
				};
			},

			findForDevice: (state: IDeviceAttributesState): ((deviceId: string) => IDeviceAttribute[]) => {
				return (deviceId: string): IDeviceAttribute[] => {
					return Object.values(state.data).filter((attribute) => attribute.device.id === deviceId);
				};
			},
		},

		actions: {
			/**
			 * Set record from via other store
			 *
			 * @param {IDeviceAttributesSetActionPayload} payload
			 */
			async set(payload: IDeviceAttributesSetActionPayload): Promise<IDeviceAttribute> {
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
			 * @param {IDeviceAttributesUnsetActionPayload} payload
			 */
			unset(payload: IDeviceAttributesUnsetActionPayload): void {
				if (typeof payload.device !== 'undefined') {
					Object.keys(this.data).forEach((id) => {
						if (id in this.data && this.data[id].device.id === payload.device?.id) {
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

				throw new Error('You have to provide at least device or attribute id');
			},

			/**
			 * Get one record from server
			 *
			 * @param {IDeviceAttributesGetActionPayload} payload
			 */
			async get(payload: IDeviceAttributesGetActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.item.includes(payload.id)) {
					return false;
				}

				this.semaphore.fetching.item.push(payload.id);

				try {
					const attributeResponse = await axios.get<IDeviceAttributeResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/attributes/${payload.id}`
					);

					const attributeResponseModel = jsonApiFormatter.deserialize(attributeResponse.data) as IDeviceAttributeResponseModel;

					this.data[attributeResponseModel.id] = await recordFactory({
						...attributeResponseModel,
						...{ deviceId: attributeResponseModel.device.id },
					});
				} catch (e: any) {
					throw new ApiError('devices-module.device-attributes.get.failed', e, 'Fetching attribute failed.');
				} finally {
					this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
				}

				return true;
			},

			/**
			 * Fetch all records from server
			 *
			 * @param {IDeviceAttributesFetchActionPayload} payload
			 */
			async fetch(payload: IDeviceAttributesFetchActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.items.includes(payload.device.id)) {
					return false;
				}

				this.semaphore.fetching.items.push(payload.device.id);

				try {
					const attributesResponse = await axios.get<IDeviceAttributesResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/attributes`
					);

					const attributesResponseModel = jsonApiFormatter.deserialize(attributesResponse.data) as IDeviceAttributeResponseModel[];

					for (const attribute of attributesResponseModel) {
						this.data[attribute.id] = await recordFactory({
							...attribute,
							...{ deviceId: attribute.device.id },
						});
					}

					this.firstLoad.push(payload.device.id);
				} catch (e: any) {
					throw new ApiError('devices-module.device-attributes.fetch.failed', e, 'Fetching attributes failed.');
				} finally {
					this.semaphore.fetching.items = this.semaphore.fetching.items.filter((item) => item !== payload.device.id);
				}

				return true;
			},

			/**
			 * Add new record
			 *
			 * @param {IDeviceAttributesAddActionPayload} payload
			 */
			async add(payload: IDeviceAttributesAddActionPayload): Promise<IDeviceAttribute> {
				const newAttribute = await recordFactory({
					...{
						id: payload?.id,
						type: payload?.type,
						draft: payload?.draft,
						deviceId: payload.device.id,
					},
					...payload.data,
				});

				this.semaphore.creating.push(newAttribute.id);

				this.data[newAttribute.id] = newAttribute;

				if (newAttribute.draft) {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newAttribute.id);

					return newAttribute;
				} else {
					try {
						const createdAttribute = await axios.post<IDeviceAttributeResponseJson>(
							`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/attributes`,
							jsonApiFormatter.serialize({
								stuff: newAttribute,
							})
						);

						const createdAttributeModel = jsonApiFormatter.deserialize(createdAttribute.data) as IDeviceAttributeResponseModel;

						this.data[createdAttributeModel.id] = await recordFactory({
							...createdAttributeModel,
							...{ deviceId: createdAttributeModel.device.id },
						});

						return this.data[createdAttributeModel.id];
					} catch (e: any) {
						// Entity could not be created on api, we have to remove it from database
						delete this.data[newAttribute.id];

						throw new ApiError('devices-module.device-attributes.create.failed', e, 'Create new attribute failed.');
					} finally {
						this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newAttribute.id);
					}
				}
			},

			/**
			 * Save draft record on server
			 *
			 * @param {IDeviceAttributesSaveActionPayload} payload
			 */
			async save(payload: IDeviceAttributesSaveActionPayload): Promise<IDeviceAttribute> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('devices-module.device-attributes.save.inProgress');
				}

				if (!Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.device-attributes.save.failed');
				}

				this.semaphore.updating.push(payload.id);

				const recordToSave = this.data[payload.id];

				try {
					const savedAttribute = await axios.post<IDeviceAttributeResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${recordToSave.device.id}/attributes`,
						jsonApiFormatter.serialize({
							stuff: recordToSave,
						})
					);

					const savedAttributeModel = jsonApiFormatter.deserialize(savedAttribute.data) as IDeviceAttributeResponseModel;

					this.data[savedAttributeModel.id] = await recordFactory({
						...savedAttributeModel,
						...{ deviceId: savedAttributeModel.device.id },
					});

					return this.data[savedAttributeModel.id];
				} catch (e: any) {
					throw new ApiError('devices-module.device-attributes.save.failed', e, 'Save draft attribute failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}
			},

			/**
			 * Remove existing record from store and server
			 *
			 * @param {IDeviceAttributesRemoveActionPayload} payload
			 */
			async remove(payload: IDeviceAttributesRemoveActionPayload): Promise<boolean> {
				if (this.semaphore.deleting.includes(payload.id)) {
					throw new Error('devices-module.device-attributes.delete.inProgress');
				}

				if (!Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.device-attributes.delete.failed');
				}

				this.semaphore.deleting.push(payload.id);

				const recordToDelete = this.data[payload.id];

				delete this.data[payload.id];

				if (recordToDelete.draft) {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				} else {
					try {
						await axios.delete(`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${recordToDelete.device.id}/attributes/${recordToDelete.id}`);
					} catch (e: any) {
						const devicesStore = useDevices();

						const device = devicesStore.findById(recordToDelete.device.id);

						if (device !== null) {
							// Deleting entity on api failed, we need to refresh entity
							await this.get({ device, id: payload.id });
						}

						throw new ApiError('devices-module.device-attributes.delete.failed', e, 'Delete attribute failed.');
					} finally {
						this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
					}
				}

				return true;
			},

			/**
			 * Receive data from sockets
			 *
			 * @param {IDeviceAttributesSocketDataActionPayload} payload
			 */
			async socketData(payload: IDeviceAttributesSocketDataActionPayload): Promise<boolean> {
				if (
					![
						RoutingKeys.DEVICE_ATTRIBUTE_ENTITY_REPORTED,
						RoutingKeys.DEVICE_ATTRIBUTE_ENTITY_CREATED,
						RoutingKeys.DEVICE_ATTRIBUTE_ENTITY_UPDATED,
						RoutingKeys.DEVICE_ATTRIBUTE_ENTITY_DELETED,
					].includes(payload.routingKey as RoutingKeys)
				) {
					return false;
				}

				const body: ExchangeEntity = JSON.parse(payload.data);

				const isValid = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema);

				if (!isValid(body)) {
					return false;
				}

				if (payload.routingKey === RoutingKeys.DEVICE_ATTRIBUTE_ENTITY_DELETED) {
					if (body.id in this.data) {
						delete this.data[body.id];
					}
				} else {
					if (body.id in this.data) {
						this.data[body.id] = await recordFactory({
							...this.data[body.id],
							...{
								id: body.id,
								identifier: body.identifier,
								name: body.name,
								content: body.content,
								deviceId: body.device,
							},
						});
					} else {
						const devicesStore = useDevices();

						const device = devicesStore.findById(body.device);

						if (device !== null) {
							await this.get({
								device,
								id: body.id,
							});
						} else {
							await devicesStore.get({ id: body.device });
						}
					}
				}

				return true;
			},
		},
	}
);
