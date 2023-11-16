import { defineStore } from 'pinia';
import axios from 'axios';
import { Jsona } from 'jsona';
import Ajv from 'ajv/dist/2020';
import { v4 as uuid } from 'uuid';
import get from 'lodash/get';
import isEqual from 'lodash/isEqual';

import exchangeEntitySchema from '../../../../../Library/Metadata/resources/schemas/modules/devices-module/document.device.property.json';
import {
	DevicePropertyEntity as ExchangeEntity,
	DevicesModuleRoutes as RoutingKeys,
	ModulePrefix,
	PropertyCategory,
	PropertyType,
} from '@fastybird/metadata-library';

import { ApiError } from '@/errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '@/jsonapi';
import { useDevices } from '@/models';
import { IDevice, IDevicePropertiesSetStateActionPayload, IPlainRelation } from '@/models/types';

import {
	IDevicePropertiesState,
	IDevicePropertiesActions,
	IDevicePropertiesGetters,
	IDevicePropertiesAddActionPayload,
	IDevicePropertiesEditActionPayload,
	IDevicePropertiesFetchActionPayload,
	IDevicePropertiesGetActionPayload,
	IDevicePropertiesRemoveActionPayload,
	IDevicePropertiesResponseJson,
	IDevicePropertiesSaveActionPayload,
	IDevicePropertiesSetActionPayload,
	IDevicePropertiesSocketDataActionPayload,
	IDevicePropertiesUnsetActionPayload,
	IDeviceProperty,
	IDevicePropertyRecordFactoryPayload,
	IDevicePropertyResponseJson,
	IDevicePropertyResponseModel,
} from './types';

const jsonSchemaValidator = new Ajv();

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const recordFactory = async (data: IDevicePropertyRecordFactoryPayload): Promise<IDeviceProperty> => {
	const devicesStore = useDevices();

	let device = devicesStore.findById(data.deviceId);

	if (device === null) {
		if (!(await devicesStore.get({ id: data.deviceId }))) {
			throw new Error("Device for property couldn't be loaded from server");
		}

		device = devicesStore.findById(data.deviceId);

		if (device === null) {
			throw new Error("Device for property couldn't be loaded from store");
		}
	}

	const record: IDeviceProperty = {
		id: get(data, 'id', uuid().toString()),
		type: { ...{ parent: 'device', entity: 'property' }, ...data.type },

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
		step: get(data, 'scale', null),

		value: get(data, 'value', null),
		actualValue: get(data, 'actualValue', null),
		expectedValue: get(data, 'expectedValue', null),
		pending: get(data, 'pending', false),
		isValid: get(data, 'isValid', false),
		command: get(data, 'command', null),
		lastResult: get(data, 'lastResult', null),
		backupValue: get(data, 'backup', null),

		// Relations
		relationshipNames: ['device', 'parent', 'children'],

		device: {
			id: device.id,
			type: device.type,
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

export const useDeviceProperties = defineStore<string, IDevicePropertiesState, IDevicePropertiesGetters, IDevicePropertiesActions>(
	'devices_module_devices_properties',
	{
		state: (): IDevicePropertiesState => {
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
			firstLoadFinished: (state: IDevicePropertiesState): ((deviceId: string) => boolean) => {
				return (deviceId) => state.firstLoad.includes(deviceId);
			},

			getting: (state: IDevicePropertiesState): ((propertyId: string) => boolean) => {
				return (propertyId) => state.semaphore.fetching.item.includes(propertyId);
			},

			fetching: (state: IDevicePropertiesState): ((deviceId: string | null) => boolean) => {
				return (deviceId) => (deviceId !== null ? state.semaphore.fetching.items.includes(deviceId) : state.semaphore.fetching.items.length > 0);
			},

			findById: (state: IDevicePropertiesState): ((id: string) => IDeviceProperty | null) => {
				return (id) => {
					const property = Object.values(state.data).find((property) => property.id === id);

					return property ?? null;
				};
			},

			findByIdentifier: (state: IDevicePropertiesState): ((device: IDevice, identifier: string) => IDeviceProperty | null) => {
				return (device: IDevice, identifier) => {
					const property = Object.values(state.data).find((property) => {
						return property.device.id === device.id && property.identifier.toLowerCase() === identifier.toLowerCase();
					});

					return property ?? null;
				};
			},

			findForDevice: (state: IDevicePropertiesState): ((deviceId: string) => IDeviceProperty[]) => {
				return (deviceId: string): IDeviceProperty[] => {
					return Object.values(state.data).filter((property) => property.device.id === deviceId);
				};
			},
		},

		actions: {
			/**
			 * Set record from via other store
			 *
			 * @param {IDevicePropertiesSetActionPayload} payload
			 */
			async set(payload: IDevicePropertiesSetActionPayload): Promise<IDeviceProperty> {
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
			 * @param {IDevicePropertiesUnsetActionPayload} payload
			 */
			unset(payload: IDevicePropertiesUnsetActionPayload): void {
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

				throw new Error('You have to provide at least device or property id');
			},

			/**
			 * Get one record from server
			 *
			 * @param {IDevicePropertiesGetActionPayload} payload
			 */
			async get(payload: IDevicePropertiesGetActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.item.includes(payload.id)) {
					return false;
				}

				this.semaphore.fetching.item.push(payload.id);

				try {
					const propertyResponse = await axios.get<IDevicePropertyResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/properties/${payload.id}`
					);

					const propertyResponseModel = jsonApiFormatter.deserialize(propertyResponse.data) as IDevicePropertyResponseModel;

					this.data[propertyResponseModel.id] = await recordFactory({
						...propertyResponseModel,
						...{ deviceId: propertyResponseModel.device.id, parentId: propertyResponseModel.parent?.id },
					});
				} catch (e: any) {
					throw new ApiError('devices-module.device-properties.get.failed', e, 'Fetching property failed.');
				} finally {
					this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
				}

				return true;
			},

			/**
			 * Fetch all records from server
			 *
			 * @param {IDevicePropertiesFetchActionPayload} payload
			 */
			async fetch(payload: IDevicePropertiesFetchActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.items.includes(payload.device.id)) {
					return false;
				}

				this.semaphore.fetching.items.push(payload.device.id);

				try {
					const propertiesResponse = await axios.get<IDevicePropertiesResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/properties`
					);

					const propertiesResponseModel = jsonApiFormatter.deserialize(propertiesResponse.data) as IDevicePropertyResponseModel[];

					for (const property of propertiesResponseModel) {
						this.data[property.id] = await recordFactory({
							...property,
							...{ deviceId: property.device.id, parentId: property.parent?.id },
						});
					}

					this.firstLoad.push(payload.device.id);
				} catch (e: any) {
					throw new ApiError('devices-module.device-properties.fetch.failed', e, 'Fetching properties failed.');
				} finally {
					this.semaphore.fetching.items = this.semaphore.fetching.items.filter((item) => item !== payload.device.id);
				}

				return true;
			},

			/**
			 * Add new record
			 *
			 * @param {IDevicePropertiesAddActionPayload} payload
			 */
			async add(payload: IDevicePropertiesAddActionPayload): Promise<IDeviceProperty> {
				const newProperty = await recordFactory({
					...{
						id: payload?.id,
						type: payload?.type,
						category: PropertyCategory.GENERIC,
						draft: payload?.draft,
						deviceId: payload.device.id,
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
						const apiData: Partial<IDeviceProperty> =
							newProperty.parent !== null
								? {
										id: newProperty.id,
										type: newProperty.type,
										identifier: newProperty.identifier,
										name: newProperty.name,
										device: newProperty.device,
										parent: newProperty.parent,
										relationshipNames: ['device', 'parent'],
								  }
								: newProperty;

						if (apiData?.type?.type === PropertyType.DYNAMIC && 'value' in apiData) {
							delete apiData.value;
						}

						const createdProperty = await axios.post<IDevicePropertyResponseJson>(
							`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/properties`,
							jsonApiFormatter.serialize({
								stuff: apiData,
							})
						);

						const createdPropertyModel = jsonApiFormatter.deserialize(createdProperty.data) as IDevicePropertyResponseModel;

						this.data[createdPropertyModel.id] = await recordFactory({
							...createdPropertyModel,
							...{ deviceId: createdPropertyModel.device.id, parentId: createdPropertyModel.parent?.id },
						});

						return this.data[createdPropertyModel.id];
					} catch (e: any) {
						// Transformer could not be created on api, we have to remove it from database
						delete this.data[newProperty.id];

						throw new ApiError('devices-module.device-properties.create.failed', e, 'Create new property failed.');
					} finally {
						this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newProperty.id);
					}
				}
			},

			/**
			 * Edit existing record
			 *
			 * @param {IDevicePropertiesEditActionPayload} payload
			 */
			async edit(payload: IDevicePropertiesEditActionPayload): Promise<IDeviceProperty> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('devices-module.device-properties.update.inProgress');
				}

				if (!Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.device-properties.update.failed');
				}

				this.semaphore.updating.push(payload.id);

				// Get record stored in database
				const existingRecord = this.data[payload.id];
				// Update with new values
				const updatedRecord = {
					...existingRecord,
					...payload.data,
					...{ parent: payload.parent ? { id: payload.parent.id, type: payload.parent.type } : existingRecord.parent },
				} as IDeviceProperty;

				this.data[payload.id] = updatedRecord;

				if (updatedRecord.draft) {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

					return this.data[payload.id];
				} else {
					try {
						const apiData: Partial<IDeviceProperty> =
							updatedRecord.parent !== null
								? {
										id: updatedRecord.id,
										type: updatedRecord.type,
										identifier: updatedRecord.identifier,
										name: updatedRecord.name,
										device: updatedRecord.device,
										parent: updatedRecord.parent,
										relationshipNames: ['device', 'parent'],
								  }
								: updatedRecord;

						if (apiData?.type?.type === PropertyType.DYNAMIC && 'value' in apiData) {
							delete apiData.value;
						}

						const updatedProperty = await axios.patch<IDevicePropertyResponseJson>(
							`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${updatedRecord.device.id}/properties/${updatedRecord.id}`,
							jsonApiFormatter.serialize({
								stuff:
									updatedRecord.parent !== null
										? {
												id: updatedRecord.id,
												type: updatedRecord.type,
												identifier: updatedRecord.identifier,
												name: updatedRecord.name,
												device: updatedRecord.device,
												parent: updatedRecord.parent,
												relationshipNames: ['device', 'parent'],
										  }
										: updatedRecord,
							})
						);

						const updatedPropertyModel = jsonApiFormatter.deserialize(updatedProperty.data) as IDevicePropertyResponseModel;

						this.data[updatedPropertyModel.id] = await recordFactory({
							...updatedPropertyModel,
							...{ deviceId: updatedPropertyModel.device.id, parentId: updatedPropertyModel.parent?.id },
						});

						return this.data[updatedPropertyModel.id];
					} catch (e: any) {
						const devicesStore = useDevices();

						const device = devicesStore.findById(updatedRecord.device.id);

						if (device !== null) {
							// Updating entity on api failed, we need to refresh entity
							await this.get({ device, id: payload.id });
						}

						throw new ApiError('devices-module.device-properties.update.failed', e, 'Edit property failed.');
					} finally {
						this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
					}
				}
			},

			/**
			 * Save property state record
			 *
			 * @param {IDevicePropertiesSetStateActionPayload} payload
			 */
			async setState(payload: IDevicePropertiesSetStateActionPayload): Promise<IDeviceProperty> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('devices-module.device-properties.update.inProgress');
				}

				if (!Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.device-properties.update.failed');
				}

				this.semaphore.updating.push(payload.id);

				// Get record stored in database
				const existingRecord = this.data[payload.id];
				// Update with new values
				this.data[payload.id] = {
					...existingRecord,
					...payload.data,
					...{ parent: payload.parent ? { id: payload.parent.id, type: payload.parent.type } : existingRecord.parent },
				} as IDeviceProperty;

				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

				return this.data[payload.id];
			},

			/**
			 * Save draft record on server
			 *
			 * @param {IDevicePropertiesSaveActionPayload} payload
			 */
			async save(payload: IDevicePropertiesSaveActionPayload): Promise<IDeviceProperty> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('devices-module.device-properties.save.inProgress');
				}

				if (!Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.device-properties.save.failed');
				}

				this.semaphore.updating.push(payload.id);

				const recordToSave = this.data[payload.id];

				try {
					const apiData: Partial<IDeviceProperty> =
						recordToSave.parent !== null
							? {
									id: recordToSave.id,
									type: recordToSave.type,
									identifier: recordToSave.identifier,
									name: recordToSave.name,
									device: recordToSave.device,
									parent: recordToSave.parent,
									relationshipNames: ['device', 'parent'],
							  }
							: recordToSave;

					if (apiData?.type?.type === PropertyType.DYNAMIC && 'value' in apiData) {
						delete apiData.value;
					}

					const savedProperty = await axios.post<IDevicePropertyResponseJson>(
						`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${recordToSave.device.id}/properties`,
						jsonApiFormatter.serialize({
							stuff: apiData,
						})
					);

					const savedPropertyModel = jsonApiFormatter.deserialize(savedProperty.data) as IDevicePropertyResponseModel;

					this.data[savedPropertyModel.id] = await recordFactory({
						...savedPropertyModel,
						...{ deviceId: savedPropertyModel.device.id, parentId: savedPropertyModel.parent?.id },
					});

					return this.data[savedPropertyModel.id];
				} catch (e: any) {
					throw new ApiError('devices-module.device-properties.save.failed', e, 'Save draft property failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}
			},

			/**
			 * Remove existing record from store and server
			 *
			 * @param {IDevicePropertiesRemoveActionPayload} payload
			 */
			async remove(payload: IDevicePropertiesRemoveActionPayload): Promise<boolean> {
				if (this.semaphore.deleting.includes(payload.id)) {
					throw new Error('devices-module.device-properties.delete.inProgress');
				}

				if (!Object.keys(this.data).includes(payload.id)) {
					throw new Error('devices-module.device-properties.delete.failed');
				}

				this.semaphore.deleting.push(payload.id);

				const recordToDelete = this.data[payload.id];

				delete this.data[payload.id];

				if (recordToDelete.draft) {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				} else {
					try {
						await axios.delete(`/${ModulePrefix.MODULE_DEVICES}/v1/devices/${recordToDelete.device.id}/properties/${recordToDelete.id}`);
					} catch (e: any) {
						const devicesStore = useDevices();

						const device = devicesStore.findById(recordToDelete.device.id);

						if (device !== null) {
							// Deleting entity on api failed, we need to refresh entity
							await this.get({ device, id: payload.id });
						}

						throw new ApiError('devices-module.device-properties.delete.failed', e, 'Delete property failed.');
					} finally {
						this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
					}
				}

				return true;
			},

			/**
			 * Receive data from sockets
			 *
			 * @param {IDevicePropertiesSocketDataActionPayload} payload
			 */
			async socketData(payload: IDevicePropertiesSocketDataActionPayload): Promise<boolean> {
				if (
					![
						RoutingKeys.DEVICE_PROPERTY_DOCUMENT_REPORTED,
						RoutingKeys.DEVICE_PROPERTY_DOCUMENT_CREATED,
						RoutingKeys.DEVICE_PROPERTY_DOCUMENT_UPDATED,
						RoutingKeys.DEVICE_PROPERTY_DOCUMENT_DELETED,
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

				if (payload.routingKey === RoutingKeys.DEVICE_PROPERTY_DOCUMENT_DELETED) {
					if (body.id in this.data) {
						delete this.data[body.id];
					}
				} else {
					if (payload.routingKey === RoutingKeys.DEVICE_PROPERTY_DOCUMENT_UPDATED && this.semaphore.updating.includes(body.id)) {
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
								deviceId: body.device,
							},
						});

						if (!isEqual(JSON.parse(JSON.stringify(this.data[body.id])), JSON.parse(JSON.stringify(record)))) {
							this.data[body.id] = record;
						}
					} else {
						const devicesStore = useDevices();

						const device = devicesStore.findById(body.device);

						if (device !== null) {
							try {
								await this.get({
									device,
									id: body.id,
								});
							} catch {
								return false;
							}
						} else {
							try {
								await devicesStore.get({ id: body.device });
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
