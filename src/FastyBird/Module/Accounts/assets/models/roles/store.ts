import { defineStore } from 'pinia';
import axios from 'axios';
import { Jsona } from 'jsona';
import Ajv from 'ajv/dist/2020';
import { v4 as uuid } from 'uuid';
import get from 'lodash/get';

import exchangeEntitySchema from '../../../../../Library/Metadata/resources/schemas/modules/accounts-module/document.role.json';
import { RoleEntity as ExchangeEntity, AccountsModuleRoutes as RoutingKeys, ModulePrefix, ModuleSource } from '@fastybird/metadata-library';

import { ApiError } from '@/errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '@/jsonapi';

import {
	IRole,
	IRolesAddActionPayload,
	IRolesEditActionPayload,
	IRoleRecordFactoryPayload,
	IRoleResponseModel,
	IRoleResponseJson,
	IRolesResponseJson,
	IRolesState,
	IRolesGetActionPayload,
	IRolesSaveActionPayload,
	IRolesRemoveActionPayload,
	IRolesSocketDataActionPayload,
} from './types';
import { IPlainRelation } from '@/models/types';

const jsonSchemaValidator = new Ajv();

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const recordFactory = (data: IRoleRecordFactoryPayload): IRole => {
	const record: IRole = {
		id: get(data, 'id', uuid().toString()),
		type: get(data, 'type', `${ModuleSource.MODULE_ACCOUNTS}/role`),

		draft: get(data, 'draft', false),

		name: data.name,
		description: get(data, 'description', null),

		anonymous: get(data, 'anonymous', false),
		authenticated: get(data, 'authenticated', false),
		administrator: get(data, 'administrator', false),

		// Relations
		relationshipNames: ['accounts'],

		accounts: [],
	};

	record.relationshipNames.forEach((relationName) => {
		get(data, relationName, []).forEach((relation: any): void => {
			if (relationName === 'accounts' && get(relation, 'id', null) !== null && get(relation, 'type', null) !== null) {
				(record[relationName] as IPlainRelation[]).push({
					id: get(relation, 'id', null) as string,
					type: get(relation, 'type', null) as string,
				});
			}
		});
	});

	return record;
};

export const useRoles = defineStore('accounts_module_roles', {
	state: (): IRolesState => {
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
		firstLoadFinished: (state): boolean => {
			return state.firstLoad;
		},

		getting: (state): ((roleId: string) => boolean) => {
			return (roleId) => state.semaphore.fetching.item.includes(roleId);
		},

		fetching: (state): boolean => {
			return state.semaphore.fetching.items;
		},
	},

	actions: {
		async get(payload: IRolesGetActionPayload): Promise<boolean> {
			if (this.semaphore.fetching.item.includes(payload.id)) {
				return false;
			}

			this.semaphore.fetching.item.push(payload.id);

			try {
				const roleResponse = await axios.get<IRoleResponseJson>(`/${ModulePrefix.MODULE_ACCOUNTS}/v1/roles/${payload.id}`);

				const roleResponseModel = jsonApiFormatter.deserialize(roleResponse.data) as IRoleResponseModel;

				this.data[roleResponseModel.id] = recordFactory(roleResponseModel);
			} catch (e: any) {
				throw new ApiError('accounts-module.roles.get.failed', e, 'Fetching role failed.');
			} finally {
				this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
			}

			return true;
		},

		async fetch(): Promise<boolean> {
			if (this.semaphore.fetching.items) {
				return false;
			}

			this.semaphore.fetching.items = true;

			try {
				const rolesResponse = await axios.get<IRolesResponseJson>(`/${ModulePrefix.MODULE_ACCOUNTS}/v1/roles`);

				const rolesResponseModel = jsonApiFormatter.deserialize(rolesResponse.data) as IRoleResponseModel[];

				rolesResponseModel.forEach((role) => (this.data[role.id] = recordFactory(role)));

				this.firstLoad = true;
			} catch (e: any) {
				throw new ApiError('accounts-module.roles.fetch.failed', e, 'Fetching roles failed.');
			} finally {
				this.semaphore.fetching.items = false;
			}

			return true;
		},

		async add(payload: IRolesAddActionPayload): Promise<IRole> {
			const newRole = recordFactory({
				...{
					id: payload?.id,
					type: payload?.type,
					draft: payload?.draft,
				},
				...payload.data,
			});

			this.semaphore.creating.push(newRole.id);

			this.data[newRole.id] = newRole;

			if (newRole.draft) {
				this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newRole.id);

				return newRole;
			} else {
				try {
					const createdRole = await axios.post<IRoleResponseJson>(
						`/${ModulePrefix.MODULE_ACCOUNTS}/v1/roles`,
						jsonApiFormatter.serialize({
							stuff: newRole,
						})
					);

					const createdRoleModel = jsonApiFormatter.deserialize(createdRole.data) as IRoleResponseModel;

					this.data[createdRoleModel.id] = recordFactory({
						...createdRoleModel,
						...{ accountId: createdRoleModel.account.id },
					});

					return this.data[createdRoleModel.id];
				} catch (e: any) {
					// Transformer could not be created on api, we have to remove it from database
					delete this.data[newRole.id];

					throw new ApiError('accounts-module.roles.create.failed', e, 'Create new role failed.');
				} finally {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newRole.id);
				}
			}
		},

		async edit(payload: IRolesEditActionPayload): Promise<IRole> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('accounts-module.roles.update.inProgress');
			}

			if (!Object.keys(this.data).includes(payload.id)) {
				throw new Error('accounts-module.roles.update.failed');
			}

			this.semaphore.updating.push(payload.id);

			// Get record stored in database
			const existingRecord = this.data[payload.id];
			// Update with new values
			const updatedRecord = { ...existingRecord, ...payload.data } as IRole;

			this.data[payload.id] = updatedRecord;

			if (updatedRecord.draft) {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

				return this.data[payload.id];
			} else {
				try {
					const updatedRole = await axios.patch<IRoleResponseJson>(
						`/${ModulePrefix.MODULE_ACCOUNTS}/v1/roles/${updatedRecord.id}`,
						jsonApiFormatter.serialize({
							stuff: updatedRecord,
						})
					);

					const updatedRoleModel = jsonApiFormatter.deserialize(updatedRole.data) as IRoleResponseModel;

					this.data[updatedRoleModel.id] = recordFactory(updatedRoleModel);

					return this.data[updatedRoleModel.id];
				} catch (e: any) {
					// Updating entity on api failed, we need to refresh entity
					await this.get({ id: payload.id });

					throw new ApiError('accounts-module.roles.update.failed', e, 'Edit role failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}
			}
		},

		async save(payload: IRolesSaveActionPayload): Promise<IRole> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('accounts-module.roles.save.inProgress');
			}

			if (!Object.keys(this.data).includes(payload.id)) {
				throw new Error('accounts-module.roles.save.failed');
			}

			this.semaphore.updating.push(payload.id);

			const recordToSave = this.data[payload.id];

			try {
				const savedRole = await axios.post<IRoleResponseJson>(
					`/${ModulePrefix.MODULE_ACCOUNTS}/v1/roles`,
					jsonApiFormatter.serialize({
						stuff: recordToSave,
					})
				);

				const savedRoleModel = jsonApiFormatter.deserialize(savedRole.data) as IRoleResponseModel;

				this.data[savedRoleModel.id] = recordFactory(savedRoleModel);

				return this.data[savedRoleModel.id];
			} catch (e: any) {
				throw new ApiError('accounts-module.roles.save.failed', e, 'Save draft role failed.');
			} finally {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
			}
		},

		async remove(payload: IRolesRemoveActionPayload): Promise<boolean> {
			if (this.semaphore.deleting.includes(payload.id)) {
				throw new Error('accounts-module.roles.delete.inProgress');
			}

			if (!Object.keys(this.data).includes(payload.id)) {
				throw new Error('accounts-module.roles.delete.failed');
			}

			this.semaphore.deleting.push(payload.id);

			const recordToDelete = this.data[payload.id];

			delete this.data[payload.id];

			if (recordToDelete.draft) {
				this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
			} else {
				try {
					await axios.delete(`/${ModulePrefix.MODULE_ACCOUNTS}/v1/roles/${recordToDelete.id}`);
				} catch (e: any) {
					// Deleting entity on api failed, we need to refresh entity
					await this.get({ id: payload.id });

					throw new ApiError('accounts-module.roles.delete.failed', e, 'Delete role failed.');
				} finally {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				}
			}

			return true;
		},

		async socketData(payload: IRolesSocketDataActionPayload): Promise<boolean> {
			if (
				![
					RoutingKeys.ROLE_DOCUMENT_REPORTED,
					RoutingKeys.ROLE_DOCUMENT_CREATED,
					RoutingKeys.ROLE_DOCUMENT_UPDATED,
					RoutingKeys.ROLE_DOCUMENT_DELETED,
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

			if (
				!Object.keys(this.data).includes(body.id) &&
				(payload.routingKey === RoutingKeys.ROLE_DOCUMENT_UPDATED || payload.routingKey === RoutingKeys.ROLE_DOCUMENT_DELETED)
			) {
				throw new Error('accounts-module.roles.update.failed');
			}

			if (payload.routingKey === RoutingKeys.ROLE_DOCUMENT_DELETED) {
				delete this.data[body.id];
			} else {
				if (payload.routingKey === RoutingKeys.ROLE_DOCUMENT_UPDATED && this.semaphore.updating.includes(body.id)) {
					return true;
				}

				const recordData = recordFactory({
					id: body.id,
					name: body.name,
					description: body.description,
					anonymous: body.anonymous,
					authenticated: body.authenticated,
					administrator: body.administrator,
				});

				if (body.id in this.data) {
					this.data[body.id] = { ...this.data[body.id], ...recordData };
				} else {
					this.data[body.id] = recordData;
				}
			}

			return true;
		},
	},
});
