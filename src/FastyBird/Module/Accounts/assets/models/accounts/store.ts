import { defineStore, Pinia, Store } from 'pinia';
import axios from 'axios';
import { Jsona } from 'jsona';
import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import { v4 as uuid } from 'uuid';
import { format } from 'date-fns';
import get from 'lodash.get';

import { AccountDocument, AccountsModuleRoutes as RoutingKeys, AccountState, ModulePrefix } from '@fastybird/metadata-library';

import exchangeDocumentSchema from '../../../resources/schemas/document.account.json';

import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { useEmails, useIdentities } from '../../models';
import {
	IAccountsActions,
	IAccountsGetters,
	IAccountsInsertDataActionPayload,
	IEmail,
	IEmailResponseModel,
	IIdentityResponseModel,
	IPlainRelation,
} from '../../models/types';

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
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = (data: IAccountRecordFactoryPayload): IAccount => {
	const record: IAccount = {
		id: get(data, 'id', uuid().toString()),
		type: data.type,

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
		if (relationName === 'emails' || relationName === 'identities' || relationName === 'roles') {
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

const addEmailsRelations = async (account: IAccount, emails: (IEmailResponseModel | IPlainRelation)[]): Promise<void> => {
	const emailsStore = useEmails();

	for (const email of emails) {
		if ('address' in email) {
			await emailsStore.set({
				data: {
					...email,
					...{
						accountId: account.id,
					},
				},
			});
		}
	}
};

const addIdentitiesRelations = async (account: IAccount, identities: (IIdentityResponseModel | IPlainRelation)[]): Promise<void> => {
	const identitiesStore = useIdentities();

	for (const identity of identities) {
		if ('uid' in identity) {
			await identitiesStore.set({
				data: {
					...identity,
					...{
						accountId: account.id,
					},
				},
			});
		}
	}
};

export const useAccounts = defineStore<string, IAccountsState, IAccountsGetters, IAccountsActions>('accounts_module_accounts', {
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
		findById: (state: IAccountsState): ((id: IAccount['id']) => IAccount | null) => {
			return (id: IAccount['id']): IAccount | null => {
				return id in state.data ? state.data[id] : null;
			};
		},
	},

	actions: {
		/**
		 * Set record from via other store
		 *
		 * @param {IAccountsSetActionPayload} payload
		 */
		async set(payload: IAccountsSetActionPayload): Promise<IAccount> {
			const record = storeRecordFactory(payload.data);

			if ('emails' in payload.data && Array.isArray(payload.data.emails)) {
				await addEmailsRelations(record, payload.data.emails);
			}

			if ('identities' in payload.data && Array.isArray(payload.data.identities)) {
				await addIdentitiesRelations(record, payload.data.identities);
			}

			return (this.data[record.id] = record);
		},

		/**
		 * Get one record from server
		 *
		 * @param {IAccountsGetActionPayload} payload
		 */
		async get(payload: IAccountsGetActionPayload): Promise<boolean> {
			if (this.semaphore.fetching.item.includes(payload.id)) {
				return false;
			}

			this.semaphore.fetching.item.push(payload.id);

			try {
				const accountResponse = await axios.get<IAccountResponseJson>(`/${ModulePrefix.ACCOUNTS}/v1/accounts/${payload.id}`);

				const accountResponseModel = jsonApiFormatter.deserialize(accountResponse.data) as IAccountResponseModel;

				this.data[accountResponseModel.id] = storeRecordFactory(accountResponseModel);

				await addEmailsRelations(this.data[accountResponseModel.id], accountResponseModel.emails);
				await addIdentitiesRelations(this.data[accountResponseModel.id], accountResponseModel.identities);
			} catch (e: any) {
				throw new ApiError('accounts-module.accounts.get.failed', e, 'Fetching account failed.');
			} finally {
				this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
			}

			const promises: Promise<boolean>[] = [];

			const emailsStore = useEmails();
			promises.push(emailsStore.fetch({ account: this.data[payload.id] }));

			const identitiesStore = useIdentities();
			promises.push(identitiesStore.fetch({ account: this.data[payload.id] }));

			Promise.all(promises).catch((e: any): void => {
				throw new ApiError('accounts-module.accounts.get.failed', e, 'Fetching account failed.');
			});

			return true;
		},

		/**
		 * Fetch all records from server
		 */
		async fetch(): Promise<boolean> {
			if (this.semaphore.fetching.items) {
				return false;
			}

			this.semaphore.fetching.items = true;

			try {
				const accountsResponse = await axios.get<IAccountsResponseJson>(`/${ModulePrefix.ACCOUNTS}/v1/accounts`);

				const accountsResponseModel = jsonApiFormatter.deserialize(accountsResponse.data) as IAccountResponseModel[];

				accountsResponseModel.forEach((account) => {
					this.data[account.id] = storeRecordFactory(account);

					addEmailsRelations(this.data[account.id], account.emails);
					addIdentitiesRelations(this.data[account.id], account.identities);
				});

				this.firstLoad = true;
			} catch (e: any) {
				throw new ApiError('accounts-module.accounts.fetch.failed', e, 'Fetching accounts failed.');
			} finally {
				this.semaphore.fetching.items = false;
			}

			const promises: Promise<boolean>[] = [];

			const emailsStore = useEmails();
			const identitiesStore = useIdentities();

			for (const account of Object.values(this.data ?? {})) {
				promises.push(emailsStore.fetch({ account }));
				promises.push(identitiesStore.fetch({ account }));
			}

			Promise.all(promises).catch((e: any): void => {
				throw new ApiError('accounts-module.accounts.fetch.failed', e, 'Fetching accounts failed.');
			});

			return true;
		},

		/**
		 * Add new record
		 *
		 * @param {IAccountsAddActionPayload} payload
		 */
		async add(payload: IAccountsAddActionPayload): Promise<IAccount> {
			const newAccount = storeRecordFactory({
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
						`/${ModulePrefix.ACCOUNTS}/v1/accounts`,
						jsonApiFormatter.serialize({
							stuff: newAccount,
						})
					);

					const createdAccountModel = jsonApiFormatter.deserialize(createdAccount.data) as IAccountResponseModel;

					this.data[createdAccountModel.id] = storeRecordFactory(createdAccountModel);
				} catch (e: any) {
					// Record could not be created on api, we have to remove it from database
					delete this.data[newAccount.id];

					throw new ApiError('accounts-module.accounts.create.failed', e, 'Create new account failed.');
				} finally {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newAccount.id);
				}

				const promises: Promise<boolean>[] = [];

				const emailsStore = useEmails();
				promises.push(emailsStore.fetch({ account: this.data[newAccount.id] }));

				const identitiesStore = useIdentities();
				promises.push(identitiesStore.fetch({ account: this.data[newAccount.id] }));

				Promise.all(promises).catch((e: any): void => {
					throw new ApiError('accounts-module.accounts.create.failed', e, 'Create new account failed.');
				});

				return this.data[newAccount.id];
			}
		},

		/**
		 * Edit existing record
		 *
		 * @param {IAccountsEditActionPayload} payload
		 */
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
						`/${ModulePrefix.ACCOUNTS}/v1/accounts/${payload.id}`,
						jsonApiFormatter.serialize({
							stuff: updatedRecord,
						})
					);

					const updatedAccountModel = jsonApiFormatter.deserialize(updatedAccount.data) as IAccountResponseModel;

					this.data[updatedAccountModel.id] = storeRecordFactory(updatedAccountModel);
				} catch (e: any) {
					// Updating record on api failed, we need to refresh record
					await this.get({ id: payload.id });

					throw new ApiError('accounts-module.accounts.update.failed', e, 'Edit account failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}

				const promises: Promise<boolean>[] = [];

				const emailsStore = useEmails();
				promises.push(emailsStore.fetch({ account: this.data[payload.id] }));

				const identitiesStore = useIdentities();
				promises.push(identitiesStore.fetch({ account: this.data[payload.id] }));

				Promise.all(promises).catch((e: any): void => {
					throw new ApiError('accounts-module.accounts.update.failed', e, 'Edit account failed.');
				});

				return this.data[payload.id];
			}
		},

		/**
		 * Save draft record on server
		 *
		 * @param {IAccountsSaveActionPayload} payload
		 */
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
					`/${ModulePrefix.ACCOUNTS}/v1/accounts`,
					jsonApiFormatter.serialize({
						stuff: recordToSave,
					})
				);

				const savedAccountModel = jsonApiFormatter.deserialize(savedAccount.data) as IAccountResponseModel;

				this.data[savedAccountModel.id] = storeRecordFactory(savedAccountModel);
			} catch (e: any) {
				throw new ApiError('accounts-module.accounts.save.failed', e, 'Save draft account failed.');
			} finally {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
			}

			const promises: Promise<boolean>[] = [];

			const emailsStore = useEmails();
			promises.push(emailsStore.fetch({ account: this.data[payload.id] }));

			const identitiesStore = useIdentities();
			promises.push(identitiesStore.fetch({ account: this.data[payload.id] }));

			Promise.all(promises).catch((e: any): void => {
				throw new ApiError('accounts-module.accounts.save.failed', e, 'Save draft account failed.');
			});

			return this.data[payload.id];
		},

		/**
		 * Remove existing record from store and server
		 *
		 * @param {IAccountsRemoveActionPayload} payload
		 */
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
					await axios.delete(`/${ModulePrefix.ACCOUNTS}/v1/accounts${payload.id}`);

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

		/**
		 * Receive data from sockets
		 *
		 * @param {IAccountsSocketDataActionPayload} payload
		 */
		async socketData(payload: IAccountsSocketDataActionPayload): Promise<boolean> {
			if (
				![
					RoutingKeys.ACCOUNT_DOCUMENT_REPORTED,
					RoutingKeys.ACCOUNT_DOCUMENT_CREATED,
					RoutingKeys.ACCOUNT_DOCUMENT_UPDATED,
					RoutingKeys.ACCOUNT_DOCUMENT_DELETED,
				].includes(payload.routingKey as RoutingKeys)
			) {
				return false;
			}

			const body: AccountDocument = JSON.parse(payload.data);

			const isValid = jsonSchemaValidator.compile<AccountDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(body)) {
					return false;
				}
			} catch {
				return false;
			}

			if (
				!Object.keys(this.data).includes(body.id) &&
				(payload.routingKey === RoutingKeys.ACCOUNT_DOCUMENT_UPDATED || payload.routingKey === RoutingKeys.ACCOUNT_DOCUMENT_DELETED)
			) {
				throw new Error('accounts-module.accounts.update.failed');
			}

			if (payload.routingKey === RoutingKeys.ACCOUNT_DOCUMENT_DELETED) {
				const recordToDelete = this.data[body.id];

				delete this.data[body.id];

				const emailsStore = useEmails();
				const identitiesStore = useIdentities();

				emailsStore.unset({ account: recordToDelete });
				identitiesStore.unset({ account: recordToDelete });
			} else {
				if (payload.routingKey === RoutingKeys.ACCOUNT_DOCUMENT_UPDATED && this.semaphore.updating.includes(body.id)) {
					return true;
				}

				const recordData = storeRecordFactory({
					id: body.id,
					type: {
						source: body.source,
						entity: 'account',
					},
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

		/**
		 * Insert data from SSR
		 *
		 * @param {IAccountsInsertDataActionPayload} payload
		 */
		async insertData(payload: IAccountsInsertDataActionPayload): Promise<boolean> {
			this.data = this.data ?? {};

			let documents: AccountDocument[] = [];

			if (Array.isArray(payload.data)) {
				documents = payload.data;
			} else {
				documents = [payload.data];
			}

			for (const doc of documents) {
				const isValid = jsonSchemaValidator.compile<AccountDocument>(exchangeDocumentSchema);

				try {
					if (!isValid(doc)) {
						return false;
					}
				} catch {
					return false;
				}

				const record = storeRecordFactory({
					...this.data[doc.id],
					...{
						id: doc.id,
						details: {
							firstName: doc.first_name,
							lastName: doc.last_name,
							middleName: doc.middle_name,
						},
						language: doc.language,
						state: doc.state,
						registered: doc.registered,
						lastVisit: doc.last_visit,
					},
				});

				if (documents.length === 1) {
					this.data[doc.id] = record;
				}
			}

			return true;
		},
	},
});

export const registerAccountsStore = (pinia: Pinia): Store => {
	return useAccounts(pinia);
};
