import { defineStore, Pinia, Store } from 'pinia';
import axios from 'axios';
import { Jsona } from 'jsona';
import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import { v4 as uuid } from 'uuid';
import get from 'lodash.get';

import { EmailDocument, AccountsModuleRoutes as RoutingKeys, ModulePrefix, ModuleSource } from '@fastybird/metadata-library';

import exchangeDocumentSchema from '../../../resources/schemas/document.email.json';

import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { useAccounts } from '../../models';
import { IAccount } from '../accounts/types';

import {
	IEmail,
	IEmailsAddActionPayload,
	IEmailsEditActionPayload,
	IEmailRecordFactoryPayload,
	IEmailResponseModel,
	IEmailResponseJson,
	IEmailsResponseJson,
	IEmailsState,
	IEmailsGetActionPayload,
	IEmailsFetchActionPayload,
	IEmailsSaveActionPayload,
	IEmailsRemoveActionPayload,
	IEmailsValidateActionPayload,
	IEmailsSocketDataActionPayload,
	IEmailsUnsetActionPayload,
	IEmailsSetActionPayload,
	IEmailsInsertDataActionPayload,
	IEmailsActions,
	IEmailsGetters,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (data: IEmailRecordFactoryPayload): Promise<IEmail> => {
	const accountsStore = useAccounts();

	let account = accountsStore.findById(data.accountId);

	if (account === null) {
		if (!(await accountsStore.get({ id: data.accountId }))) {
			throw new Error("Account for email couldn't be loaded from server");
		}

		account = accountsStore.findById(data.accountId);

		if (account === null) {
			throw new Error("Account for email couldn't be loaded from store");
		}
	}

	return {
		id: get(data, 'id', uuid().toString()),
		type: data.type,

		draft: get(data, 'draft', false),

		address: data.address,
		default: get(data, 'default', false),
		private: get(data, 'private', false),
		verified: get(data, 'verified', false),

		// Relations
		relationshipNames: ['account'],

		account: {
			id: account.id,
			type: account.type,
		},

		// Entity transformers
		get isDefault(): boolean {
			return this.default;
		},

		get isPrivate(): boolean {
			return this.private;
		},

		get isVerified(): boolean {
			return this.verified;
		},
	} as IEmail;
};

export const useEmails = defineStore<string, IEmailsState, IEmailsGetters, IEmailsActions>('accounts_module_emails', {
	state: (): IEmailsState => {
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
		firstLoadFinished: (state: IEmailsState): ((accountId: IAccount['id']) => boolean) => {
			return (accountId: IAccount['id']): boolean => state.firstLoad.includes(accountId);
		},

		getting: (state: IEmailsState): ((id: IEmail['id']) => boolean) => {
			return (id: IEmail['id']): boolean => state.semaphore.fetching.item.includes(id);
		},

		fetching: (state: IEmailsState): ((accountId: IAccount['id'] | null) => boolean) => {
			return (accountId: IAccount['id'] | null): boolean =>
				accountId !== null ? state.semaphore.fetching.items.includes(accountId) : state.semaphore.fetching.items.length > 0;
		},

		findById: (state: IEmailsState): ((id: IEmail['id']) => IEmail | null) => {
			return (id: IEmail['id']): IEmail | null => {
				const email = Object.values(state.data).find((email) => email.id === id);

				return email ?? null;
			};
		},

		findByAddress: (state: IEmailsState): ((address: IEmail['address']) => IEmail | null) => {
			return (address: IEmail['address']): IEmail | null => {
				const email = Object.values(state.data).find((email) => email.address.toLowerCase() === address.toLowerCase());

				return email ?? null;
			};
		},

		findForAccount: (state: IEmailsState): ((accountId: IAccount['id']) => IEmail[]) => {
			return (accountId: IAccount['id']): IEmail[] => {
				return Object.values(state.data).filter((email) => email.account.id === accountId);
			};
		},
	},

	actions: {
		/**
		 * Set record from via other store
		 *
		 * @param {IEmailsSetActionPayload} payload
		 */
		async set(payload: IEmailsSetActionPayload): Promise<IEmail> {
			const record = await storeRecordFactory(payload.data);

			return (this.data[record.id] = record);
		},

		/**
		 * Unset record from via other store
		 *
		 * @param {IEmailsUnsetActionPayload} payload
		 */
		unset(payload: IEmailsUnsetActionPayload): void {
			if (payload.account !== undefined) {
				Object.keys(this.data).forEach((id) => {
					if (id in this.data && this.data[id].account.id === payload.account?.id) {
						delete this.data[id];
					}
				});

				return;
			} else if (payload.id !== undefined) {
				if (payload.id in this.data) {
					delete this.data[payload.id];
				}

				return;
			}

			throw new Error('You have to provide at least account or email id');
		},

		/**
		 * Get one record from server
		 *
		 * @param {IEmailsGetActionPayload} payload
		 */
		async get(payload: IEmailsGetActionPayload): Promise<boolean> {
			if (this.semaphore.fetching.item.includes(payload.id)) {
				return false;
			}

			this.semaphore.fetching.item.push(payload.id);

			try {
				const emailResponse = await axios.get<IEmailResponseJson>(`/${ModulePrefix.ACCOUNTS}/v1/accounts/${payload.account.id}/emails/${payload.id}`);

				const emailResponseModel = jsonApiFormatter.deserialize(emailResponse.data) as IEmailResponseModel;

				this.data[emailResponseModel.id] = await storeRecordFactory({
					...emailResponseModel,
					...{ accountId: emailResponseModel.account.id },
				});
			} catch (e: any) {
				throw new ApiError('accounts-module.emails.get.failed', e, 'Fetching email failed.');
			} finally {
				this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
			}

			return true;
		},

		/**
		 * Fetch all records from server
		 *
		 * @param {IEmailsFetchActionPayload} payload
		 */
		async fetch(payload: IEmailsFetchActionPayload): Promise<boolean> {
			if (this.semaphore.fetching.items.includes(payload.account.id)) {
				return false;
			}

			this.semaphore.fetching.items.push(payload.account.id);

			try {
				const emailsResponse = await axios.get<IEmailsResponseJson>(`/${ModulePrefix.ACCOUNTS}/v1/accounts/${payload.account.id}/emails`);

				const emailsResponseModel = jsonApiFormatter.deserialize(emailsResponse.data) as IEmailResponseModel[];

				for (const email of emailsResponseModel) {
					this.data[email.id] = await storeRecordFactory({
						...email,
						...{ accountId: email.account.id },
					});
				}

				this.firstLoad.push(payload.account.id);
			} catch (e: any) {
				throw new ApiError('accounts-module.emails.fetch.failed', e, 'Fetching emails failed.');
			} finally {
				this.semaphore.fetching.items = this.semaphore.fetching.items.filter((item) => item !== payload.account.id);
			}

			return true;
		},

		/**
		 * Add new record
		 *
		 * @param {IEmailsAddActionPayload} payload
		 */
		async add(payload: IEmailsAddActionPayload): Promise<IEmail> {
			const newEmail = await storeRecordFactory({
				...{
					id: payload?.id,
					type: payload?.type,
					draft: payload?.draft,
					accountId: payload.account.id,
				},
				...payload.data,
			});

			this.semaphore.creating.push(newEmail.id);

			this.data[newEmail.id] = newEmail;

			if (newEmail.draft) {
				this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newEmail.id);

				return newEmail;
			} else {
				try {
					const createdEmail = await axios.post<IEmailResponseJson>(
						`/${ModulePrefix.ACCOUNTS}/v1/accounts/${payload.account.id}/emails`,
						jsonApiFormatter.serialize({
							stuff: newEmail,
						})
					);

					const createdEmailModel = jsonApiFormatter.deserialize(createdEmail.data) as IEmailResponseModel;

					this.data[createdEmailModel.id] = await storeRecordFactory({
						...createdEmailModel,
						...{ accountId: createdEmailModel.account.id },
					});

					return this.data[createdEmailModel.id];
				} catch (e: any) {
					// Entity could not be created on api, we have to remove it from database
					delete this.data[newEmail.id];

					throw new ApiError('accounts-module.emails.create.failed', e, 'Create new email failed.');
				} finally {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newEmail.id);
				}
			}
		},

		/**
		 * Edit existing record
		 *
		 * @param {IEmailsEditActionPayload} payload
		 */
		async edit(payload: IEmailsEditActionPayload): Promise<IEmail> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('accounts-module.emails.update.inProgress');
			}

			if (!Object.keys(this.data).includes(payload.id)) {
				throw new Error('accounts-module.emails.update.failed');
			}

			this.semaphore.updating.push(payload.id);

			// Get record stored in database
			const existingRecord = this.data[payload.id];
			// Update with new values
			const updatedRecord = { ...existingRecord, ...payload.data } as IEmail;

			this.data[payload.id] = updatedRecord;

			if (updatedRecord.draft) {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

				return this.data[payload.id];
			} else {
				try {
					const updatedEmail = await axios.patch<IEmailResponseJson>(
						`/${ModulePrefix.ACCOUNTS}/v1/accounts/${updatedRecord.account.id}/emails/${updatedRecord.id}`,
						jsonApiFormatter.serialize({
							stuff: updatedRecord,
						})
					);

					const updatedEmailModel = jsonApiFormatter.deserialize(updatedEmail.data) as IEmailResponseModel;

					this.data[updatedEmailModel.id] = await storeRecordFactory({
						...updatedEmailModel,
						...{ accountId: updatedEmailModel.account.id },
					});

					return this.data[updatedEmailModel.id];
				} catch (e: any) {
					const accountsStore = useAccounts();

					const account = accountsStore.findById(updatedRecord.account.id);

					if (account !== null) {
						// Updating entity on api failed, we need to refresh entity
						await this.get({ account, id: payload.id });
					}

					throw new ApiError('accounts-module.emails.update.failed', e, 'Edit email failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}
			}
		},

		/**
		 * Save draft record on server
		 *
		 * @param {IEmailsSaveActionPayload} payload
		 */
		async save(payload: IEmailsSaveActionPayload): Promise<IEmail> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('accounts-module.emails.save.inProgress');
			}

			if (!Object.keys(this.data).includes(payload.id)) {
				throw new Error('accounts-module.emails.save.failed');
			}

			this.semaphore.updating.push(payload.id);

			const recordToSave = this.data[payload.id];

			try {
				const savedEmail = await axios.post<IEmailResponseJson>(
					`/${ModulePrefix.ACCOUNTS}/v1/accounts/${recordToSave.account.id}/emails`,
					jsonApiFormatter.serialize({
						stuff: recordToSave,
					})
				);

				const savedEmailModel = jsonApiFormatter.deserialize(savedEmail.data) as IEmailResponseModel;

				this.data[savedEmailModel.id] = await storeRecordFactory({
					...savedEmailModel,
					...{ accountId: savedEmailModel.account.id },
				});

				return this.data[savedEmailModel.id];
			} catch (e: any) {
				throw new ApiError('accounts-module.emails.save.failed', e, 'Save draft email failed.');
			} finally {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
			}
		},

		/**
		 * Remove existing record from store and server
		 *
		 * @param {IEmailsRemoveActionPayload} payload
		 */
		async remove(payload: IEmailsRemoveActionPayload): Promise<boolean> {
			if (this.semaphore.deleting.includes(payload.id)) {
				throw new Error('accounts-module.emails.delete.inProgress');
			}

			if (!Object.keys(this.data).includes(payload.id)) {
				throw new Error('accounts-module.emails.delete.failed');
			}

			this.semaphore.deleting.push(payload.id);

			const recordToDelete = this.data[payload.id];

			delete this.data[payload.id];

			if (recordToDelete.draft) {
				this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
			} else {
				try {
					await axios.delete(`/${ModulePrefix.ACCOUNTS}/v1/accounts/${recordToDelete.account.id}/emails/${recordToDelete.id}`);
				} catch (e: any) {
					const accountsStore = useAccounts();

					const account = accountsStore.findById(recordToDelete.account.id);

					if (account !== null) {
						// Deleting entity on api failed, we need to refresh entity
						await this.get({ account, id: payload.id });
					}

					throw new ApiError('accounts-module.emails.delete.failed', e, 'Delete email failed.');
				} finally {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				}
			}

			return true;
		},

		/**
		 * Validate entity value
		 *
		 * @param {IEmailsValidateActionPayload} payload
		 */
		async validate(payload: IEmailsValidateActionPayload): Promise<any> {
			try {
				const validateResponse = await axios.post(
					`/${ModulePrefix.ACCOUNTS}/v1/validate-email`,
					jsonApiFormatter.serialize({
						stuff: {
							type: `${ModuleSource.ACCOUNTS}/email`,

							address: payload.address,
						},
					})
				);

				return validateResponse.status >= 200 && validateResponse.status < 300;
			} catch (e: any) {
				throw new ApiError('accounts-module.emails.validate.failed', e, 'Validate email address failed.');
			}
		},

		/**
		 * Receive data from sockets
		 *
		 * @param {IEmailsSocketDataActionPayload} payload
		 */
		async socketData(payload: IEmailsSocketDataActionPayload): Promise<boolean> {
			if (
				![
					RoutingKeys.EMAIL_DOCUMENT_REPORTED,
					RoutingKeys.EMAIL_DOCUMENT_CREATED,
					RoutingKeys.EMAIL_DOCUMENT_UPDATED,
					RoutingKeys.EMAIL_DOCUMENT_DELETED,
				].includes(payload.routingKey as RoutingKeys)
			) {
				return false;
			}

			const body: EmailDocument = JSON.parse(payload.data);

			const isValid = jsonSchemaValidator.compile<EmailDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(body)) {
					return false;
				}
			} catch {
				return false;
			}

			if (
				!Object.keys(this.data).includes(body.id) &&
				(payload.routingKey === RoutingKeys.EMAIL_DOCUMENT_UPDATED || payload.routingKey === RoutingKeys.EMAIL_DOCUMENT_DELETED)
			) {
				throw new Error('accounts-module.emails.update.failed');
			}

			if (payload.routingKey === RoutingKeys.EMAIL_DOCUMENT_DELETED) {
				delete this.data[body.id];
			} else {
				if (payload.routingKey === RoutingKeys.EMAIL_DOCUMENT_UPDATED && this.semaphore.updating.includes(body.id)) {
					return true;
				}

				const recordData = await storeRecordFactory({
					id: body.id,
					type: {
						source: body.source,
						entity: 'email',
					},
					address: body.address,
					default: body.default,
					verified: body.verified,
					private: body.private,
					accountId: body.account,
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
		 * @param {IEmailsInsertDataActionPayload} payload
		 */
		async insertData(payload: IEmailsInsertDataActionPayload): Promise<boolean> {
			this.data = this.data ?? {};

			let documents: EmailDocument[] = [];

			if (Array.isArray(payload.data)) {
				documents = payload.data;
			} else {
				documents = [payload.data];
			}

			const accountIds = [];

			for (const doc of documents) {
				const isValid = jsonSchemaValidator.compile<EmailDocument>(exchangeDocumentSchema);

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
						address: doc.address,
						default: doc.default,
						public: doc.public,
						private: doc.private,
						verified: doc.verified,
						accountId: doc.account,
					},
				});

				if (documents.length === 1) {
					this.data[doc.id] = record;
				}

				accountIds.push(doc.account);
			}

			if (documents.length > 1) {
				const uniqueAccountIds = [...new Set(accountIds)];

				if (uniqueAccountIds.length > 1) {
					this.firstLoad = [...new Set(this.firstLoad)];
				}

				for (const deviceId of uniqueAccountIds) {
					this.firstLoad.push(deviceId);
					this.firstLoad = [...new Set(this.firstLoad)];
				}
			}

			return true;
		},
	},
});

export const registerEmailsStore = (pinia: Pinia): Store => {
	return useEmails(pinia);
};
