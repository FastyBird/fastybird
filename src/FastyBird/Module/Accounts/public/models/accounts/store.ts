import { defineStore } from 'pinia';
import axios from 'axios';
import JsonaService from 'jsona';
import Ajv from 'ajv';
import { v4 as uuid } from 'uuid';
import { format } from 'date-fns';
import get from 'lodash/get';

import exchangeEntitySchema from '@fastybird/metadata-library/resources/schemas/modules/accounts-module/entity.account.json';
import {
	AccountEntity as ExchangeEntity,
	AccountsModuleRoutes as RoutingKeys,
	AccountState,
	ModulePrefix,
	ModuleSource,
} from '@fastybird/metadata-library';

import { ApiError } from '@/errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '@/jsonapi';
import { useEmails, useIdentities } from '@/models';
import { IEmail, IEmailResponseModel, IIdentityResponseModel, IPlainRelation } from '@/types';

import {
	IAccount,
	IAccountsAddActionPayload,
	IAccountsGetActionPayload,
	IAccountRecordFactoryPayload,
	IAccountsRemoveActionPayload,
	IAccountsSetActionPayload,
	IAccountResponseJson,
	IAccountResponseModel,
	IAccountsSaveActionPayload,
	IAccountsSocketDataActionPayload,
	IAccountsResponseJson,
	IAccountsState,
	IAccountsEditActionPayload,
} from './types';

const jsonSchemaValidator = new Ajv();

const jsonApiFormatter = new JsonaService({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const recordFactory = (data: IAccountRecordFactoryPayload): IAccount => {
	const record: IAccount = {
		id: get(data, 'id', uuid().toString()),
		type: get(data, 'type', `${ModuleSource.MODULE_ACCOUNTS}/account`),

		draft: get(data, 'draft', false),

		details: {
			firstName: data.details.firstName,
			lastName: data.details.lastName,
			middleName: get(data, 'details.middleName', null),
		},

		language: get(data, 'language', 'en'),

		weekStart: get(data, 'weekStart', 1),

		dateTime: {
			timezone: get(data, 'dateTime.timezone', 'Europe/Prague'),
			dateFormat: get(data, 'dateTime.dateFormat', 'dd.MM.yyyy'),
			timeFormat: get(data, 'dateTime.timeFormat', 'HH:mm'),
		},

		state: get(data, 'state', AccountState.NOT_ACTIVATED),

		lastVisit: null,
		registered: format(new Date(), "yyyy-MM-dd'T'HH:mm:ssXXXXX"),

		relationshipNames: ['emails', 'identities', 'roles'],

		emails: [],
		identities: [],
		roles: [],

		get name(): string {
			return `${this.details.firstName} ${this.details.lastName}`;
		},

		get email(): IEmail | null {
			const emailsStore = useEmails();

			const defaultEmail = emailsStore.findForAccount(this.id).find((email) => email.isDefault);

			return defaultEmail ?? null;
		},
	};

	record.relationshipNames.forEach((relationName) => {
		get(data, relationName, []).forEach((relation: any): void => {
			if (
				relationName === 'emails' ||
				relationName === 'identities' ||
				(relationName === 'roles' && get(relation, 'id', null) !== null && get(relation, 'type', null) !== null)
			) {
				(record[relationName] as IPlainRelation[]).push({
					id: get(relation, 'id', null) as string,
					type: get(relation, 'type', null) as string,
				});
			}
		});
	});

	return record;
};

const addEmailsRelations = (account: IAccount, emails: IEmailResponseModel[]): void => {
	const emailsStore = useEmails();

	emails.forEach((email) => {
		emailsStore.set({
			data: {
				...email,
				...{
					accountId: account.id,
				},
			},
		});
	});
};

const addIdentitiesRelations = (account: IAccount, identities: IIdentityResponseModel[]): void => {
	const identitiesStore = useIdentities();

	identities.forEach((identity) => {
		identitiesStore.set({
			data: {
				...identity,
				...{
					accountId: account.id,
				},
			},
		});
	});
};

export const useAccounts = defineStore('accounts_module_accounts', {
	state: (): IAccountsState => {
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
		findById: (state): ((id: string) => IAccount | null) => {
			return (id: string): IAccount | null => {
				return id in state.data ? state.data[id] : null;
			};
		},
	},

	actions: {
		async set(payload: IAccountsSetActionPayload): Promise<IAccount> {
			const record = await recordFactory(payload.data);

			if ('emails' in payload.data && Array.isArray(payload.data.emails)) {
				addEmailsRelations(record, payload.data.emails);
			}

			if ('identities' in payload.data && Array.isArray(payload.data.identities)) {
				addIdentitiesRelations(record, payload.data.identities);
			}

			return (this.data[record.id] = record);
		},

		async get(payload: IAccountsGetActionPayload): Promise<boolean> {
			if (this.semaphore.fetching.item.includes(payload.id)) {
				return false;
			}

			this.semaphore.fetching.item.push(payload.id);

			try {
				const accountResponse = await axios.get<IAccountResponseJson>(
					`/${ModulePrefix.MODULE_ACCOUNTS}/v1/accounts/${payload.id}?include=emails,identities`
				);

				const accountResponseModel = jsonApiFormatter.deserialize(accountResponse.data) as IAccountResponseModel;

				this.data[accountResponseModel.id] = recordFactory(accountResponseModel);

				addEmailsRelations(this.data[accountResponseModel.id], accountResponseModel.emails);
				addIdentitiesRelations(this.data[accountResponseModel.id], accountResponseModel.identities);
			} catch (e: any) {
				throw new ApiError('accounts-module.accounts.get.failed', e, 'Fetching account failed.');
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
				const accountsResponse = await axios.get<IAccountsResponseJson>(`/${ModulePrefix.MODULE_ACCOUNTS}/v1/accounts?include=emails,identities`);

				const accountsResponseModel = jsonApiFormatter.deserialize(accountsResponse.data) as IAccountResponseModel[];

				accountsResponseModel.forEach((account) => {
					this.data[account.id] = recordFactory(account);

					addEmailsRelations(this.data[account.id], account.emails);
					addIdentitiesRelations(this.data[account.id], account.identities);
				});

				this.firstLoad = true;
			} catch (e: any) {
				throw new ApiError('accounts-module.accounts.fetch.failed', e, 'Fetching accounts failed.');
			} finally {
				this.semaphore.fetching.items = false;
			}

			return true;
		},

		async add(payload: IAccountsAddActionPayload): Promise<IAccount> {
			const newAccount = recordFactory({
				...{ id: payload?.id, type: payload?.type, draft: payload?.draft },
				...payload.data,
			});

			this.semaphore.creating.push(newAccount.id);

			this.data[newAccount.id] = newAccount;

			if (newAccount.draft) {
				this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newAccount.id);

				return newAccount;
			} else {
				try {
					const createdAccount = await axios.post<IAccountResponseJson>(
						`/${ModulePrefix.MODULE_ACCOUNTS}/v1/accounts?include=emails,identities`,
						jsonApiFormatter.serialize({
							stuff: newAccount,
						})
					);

					const createdAccountModel = jsonApiFormatter.deserialize(createdAccount.data) as IAccountResponseModel;

					this.data[createdAccountModel.id] = recordFactory(createdAccountModel);

					addEmailsRelations(this.data[createdAccountModel.id], createdAccountModel.emails);
					addIdentitiesRelations(this.data[createdAccountModel.id], createdAccountModel.identities);

					return this.data[createdAccountModel.id];
				} catch (e: any) {
					// Record could not be created on api, we have to remove it from database
					delete this.data[newAccount.id];

					throw new ApiError('accounts-module.accounts.create.failed', e, 'Create new account failed.');
				} finally {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newAccount.id);
				}
			}
		},

		async edit(payload: IAccountsEditActionPayload): Promise<IAccount> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('accounts-module.accounts.update.inProgress');
			}

			if (!Object.keys(this.data).includes(payload.id)) {
				throw new Error('accounts-module.accounts.update.failed');
			}

			this.semaphore.updating.push(payload.id);

			// Get record stored in database
			const existingRecord = this.data[payload.id];
			// Update with new values
			const updatedRecord = { ...existingRecord, ...payload.data } as IAccount;

			this.data[payload.id] = updatedRecord;

			if (updatedRecord.draft) {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

				return this.data[payload.id];
			} else {
				try {
					const updatedAccount = await axios.patch<IAccountResponseJson>(
						`/${ModulePrefix.MODULE_ACCOUNTS}/v1/accounts/${payload.id}?include=emails,identities`,
						jsonApiFormatter.serialize({
							stuff: updatedRecord,
						})
					);

					const updatedAccountModel = jsonApiFormatter.deserialize(updatedAccount.data) as IAccountResponseModel;

					this.data[updatedAccountModel.id] = recordFactory(updatedAccountModel);

					addEmailsRelations(this.data[updatedAccountModel.id], updatedAccountModel.emails);
					addIdentitiesRelations(this.data[updatedAccountModel.id], updatedAccountModel.identities);

					return this.data[updatedAccountModel.id];
				} catch (e: any) {
					// Updating record on api failed, we need to refresh record
					await this.get({ id: payload.id });

					throw new ApiError('accounts-module.accounts.update.failed', e, 'Edit account failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}
			}
		},

		async save(payload: IAccountsSaveActionPayload): Promise<IAccount> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('accounts-module.accounts.save.inProgress');
			}

			if (!Object.keys(this.data).includes(payload.id)) {
				throw new Error('accounts-module.accounts.save.failed');
			}

			this.semaphore.updating.push(payload.id);

			const recordToSave = this.data[payload.id];

			try {
				const savedAccount = await axios.post<IAccountResponseJson>(
					`/${ModulePrefix.MODULE_ACCOUNTS}/v1/accounts?include=emails,identities`,
					jsonApiFormatter.serialize({
						stuff: recordToSave,
					})
				);

				const savedAccountModel = jsonApiFormatter.deserialize(savedAccount.data) as IAccountResponseModel;

				this.data[savedAccountModel.id] = recordFactory(savedAccountModel);

				addEmailsRelations(this.data[savedAccountModel.id], savedAccountModel.emails);
				addIdentitiesRelations(this.data[savedAccountModel.id], savedAccountModel.identities);

				return this.data[savedAccountModel.id];
			} catch (e: any) {
				throw new ApiError('accounts-module.accounts.save.failed', e, 'Save draft account failed.');
			} finally {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
			}
		},

		async remove(payload: IAccountsRemoveActionPayload): Promise<boolean> {
			if (this.semaphore.deleting.includes(payload.id)) {
				throw new Error('accounts-module.accounts.delete.inProgress');
			}

			if (!Object.keys(this.data).includes(payload.id)) {
				return true;
			}

			const emailsStore = useEmails();
			const identitiesStore = useIdentities();

			this.semaphore.deleting.push(payload.id);

			const recordToDelete = this.data[payload.id];

			delete this.data[payload.id];

			if (recordToDelete.draft) {
				this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);

				emailsStore.unset({ account: recordToDelete });
				identitiesStore.unset({ account: recordToDelete });
			} else {
				try {
					await axios.delete(`/${ModulePrefix.MODULE_ACCOUNTS}/v1/accounts${payload.id}`);

					emailsStore.unset({ account: recordToDelete });
					identitiesStore.unset({ account: recordToDelete });
				} catch (e: any) {
					// Deleting record on api failed, we need to refresh record
					await this.get({ id: payload.id });

					throw new ApiError('accounts-module.accounts.delete.failed', e, 'Delete account failed.');
				} finally {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				}
			}

			return true;
		},

		async socketData(payload: IAccountsSocketDataActionPayload): Promise<boolean> {
			if (
				![
					RoutingKeys.ACCOUNT_ENTITY_REPORTED,
					RoutingKeys.ACCOUNT_ENTITY_CREATED,
					RoutingKeys.ACCOUNT_ENTITY_UPDATED,
					RoutingKeys.ACCOUNT_ENTITY_DELETED,
				].includes(payload.routingKey as RoutingKeys)
			) {
				return false;
			}

			const body: ExchangeEntity = JSON.parse(payload.data);

			const isValid = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema);

			if (!isValid(body)) {
				return false;
			}

			if (
				!Object.keys(this.data).includes(body.id) &&
				(payload.routingKey === RoutingKeys.ACCOUNT_ENTITY_UPDATED || payload.routingKey === RoutingKeys.ACCOUNT_ENTITY_DELETED)
			) {
				throw new Error('accounts-module.accounts.update.failed');
			}

			if (payload.routingKey === RoutingKeys.ACCOUNT_ENTITY_DELETED) {
				const recordToDelete = this.data[body.id];

				delete this.data[body.id];

				const emailsStore = useEmails();
				const identitiesStore = useIdentities();

				emailsStore.unset({ account: recordToDelete });
				identitiesStore.unset({ account: recordToDelete });
			} else {
				if (payload.routingKey === RoutingKeys.ACCOUNT_ENTITY_UPDATED && this.semaphore.updating.includes(body.id)) {
					return true;
				}

				const recordData = recordFactory({
					id: body.id,
					details: {
						firstName: body.first_name,
						lastName: body.last_name,
						middleName: body.middle_name,
					},
					language: body.language,
					registered: body.registered,
					lastVisit: body.last_visit,
					state: body.state,
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
