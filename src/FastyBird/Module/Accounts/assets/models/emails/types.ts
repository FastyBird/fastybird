import { _GettersTree } from 'pinia';
import { TJsonaModel, TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';

import { EmailDocument } from '@fastybird/metadata-library';

import { IAccount, IAccountResponseData, IEntityMeta, IPlainRelation } from '../../models/types';

export interface IEmailMeta extends IEntityMeta {
	entity: 'email';
}

// STORE
// =====

export interface IEmailsState {
	semaphore: IEmailsStateSemaphore;
	firstLoad: IEmail['id'][];
	data: { [key: IEmail['id']]: IEmail };
}

export interface IEmailsGetters extends _GettersTree<IEmailsState> {
	firstLoadFinished: (state: IEmailsState) => (accountId: IAccount['id']) => boolean;
	getting: (state: IEmailsState) => (id: IEmail['id']) => boolean;
	fetching: (state: IEmailsState) => (accountId: IAccount['id'] | null) => boolean;
	findById: (state: IEmailsState) => (id: IEmail['id']) => IEmail | null;
	findByAddress: (state: IEmailsState) => (address: IEmail['address']) => IEmail | null;
	findForAccount: (state: IEmailsState) => (accountId: IAccount['id']) => IEmail[];
}

export interface IEmailsActions {
	set: (payload: IEmailsSetActionPayload) => Promise<IEmail>;
	unset: (payload: IEmailsUnsetActionPayload) => void;
	get: (payload: IEmailsGetActionPayload) => Promise<boolean>;
	fetch: (payload: IEmailsFetchActionPayload) => Promise<boolean>;
	add: (payload: IEmailsAddActionPayload) => Promise<IEmail>;
	edit: (payload: IEmailsEditActionPayload) => Promise<IEmail>;
	save: (payload: IEmailsSaveActionPayload) => Promise<IEmail>;
	remove: (payload: IEmailsRemoveActionPayload) => Promise<boolean>;
	validate: (payload: IEmailsValidateActionPayload) => Promise<any>;
	socketData: (payload: IEmailsSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IEmailsInsertDataActionPayload) => Promise<boolean>;
}

// STORE STATE
// ===========

export interface IEmailsStateSemaphore {
	fetching: IEmailsStateSemaphoreFetching;
	creating: IEmail['id'][];
	updating: IEmail['id'][];
	deleting: IEmail['id'][];
}

interface IEmailsStateSemaphoreFetching {
	items: IAccount['id'][];
	item: IEmail['id'][];
}

// STORE MODELS
// ============

export interface IEmail {
	id: string;
	type: IEmailMeta;

	draft: boolean;

	address: string;
	default: boolean;
	private: boolean;
	verified: boolean;

	// Relations
	relationshipNames: string[];

	account: IPlainRelation;

	// Entity transformers
	isDefault: boolean;
	isPrivate: boolean;
	isVerified: boolean;
}

// STORE DATA FACTORIES
// ====================

export interface IEmailRecordFactoryPayload {
	id?: IEmail['id'];
	type: IEmailMeta;

	draft?: IEmail['draft'];

	address: IEmail['address'];
	default?: IEmail['default'];
	private?: IEmail['private'];
	verified?: IEmail['verified'];

	// Relations
	relationshipNames?: IEmail['relationshipNames'];

	accountId: IAccount['id'];
}

// STORE ACTIONS
// =============

export interface IEmailsSetActionPayload {
	data: IEmailRecordFactoryPayload;
}

export interface IEmailsUnsetActionPayload {
	account?: IAccount;
	id?: IEmail['id'];
}

export interface IEmailsGetActionPayload {
	account: IAccount;
	id: IEmail['id'];
}

export interface IEmailsFetchActionPayload {
	account: IAccount;
}

export interface IEmailsAddActionPayload {
	id?: IEmail['id'];
	type: IEmailMeta;

	draft?: IEmail['draft'];

	account: IAccount;

	data: {
		address: IEmail['address'];
		default?: IEmail['default'];
		private?: IEmail['private'];
	};
}

export interface IEmailsEditActionPayload {
	id: IEmail['id'];

	data: {
		default?: IEmail['default'];
		private?: IEmail['private'];
	};
}

export interface IEmailsSaveActionPayload {
	id: IEmail['id'];
}

export interface IEmailsRemoveActionPayload {
	id: IEmail['id'];
}

export interface IEmailsValidateActionPayload {
	address: IEmail['address'];
}

export interface IEmailsSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IEmailsInsertDataActionPayload {
	data: EmailDocument | EmailDocument[];
}

// API RESPONSES JSONS
// ===================

export interface IEmailResponseJson extends TJsonApiBody {
	data: IEmailResponseData;
	includes?: IAccountResponseData[];
}

export interface IEmailsResponseJson extends TJsonApiBody {
	data: IEmailResponseData[];
	includes?: IAccountResponseData[];
}

export interface IEmailResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IEmailResponseDataAttributes;
	relationships: IEmailResponseDataRelationships;
}

interface IEmailResponseDataAttributes {
	address: string;
	default: boolean;
	private: boolean;
	verified: boolean;
}

interface IEmailResponseDataRelationships extends TJsonApiRelationships {
	account: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IEmailResponseModel extends TJsonaModel {
	id: string;
	type: IEmailMeta;

	address: string;
	default: boolean;
	private: boolean;
	verified: boolean;

	// Relations
	relationshipNames: string[];

	account: IPlainRelation;
}
