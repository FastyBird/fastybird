import { TJsonaModel, TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';

import { IAccount, IAccountResponseData, IPlainRelation } from '@/types';

// STORE STATE
// ===========

export interface IEmailsState {
	semaphore: IEmailsStateSemaphore;
	firstLoad: string[];
	data: { [key: string]: IEmail };
}

export interface IEmailsStateSemaphore {
	fetching: IEmailsStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

interface IEmailsStateSemaphoreFetching {
	items: string[];
	item: string[];
}

// STORE MODELS
// ============

export interface IEmail {
	id: string;
	type: string;

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
	id?: string;
	type?: string;

	draft?: boolean;

	address: string;
	default?: boolean;
	public?: boolean;
	private?: boolean;
	verified?: boolean;

	// Relations
	relationshipNames?: string[];

	accountId: string;
}

// STORE ACTIONS
// =============

export interface IEmailsSetActionPayload {
	data: IEmailRecordFactoryPayload;
}

export interface IEmailsUnsetActionPayload {
	account?: IAccount;
	id?: string;
}

export interface IEmailsGetActionPayload {
	account: IAccount;
	id: string;
}

export interface IEmailsFetchActionPayload {
	account: IAccount;
}

export interface IEmailsAddActionPayload {
	id?: string;
	type?: string;

	draft?: boolean;

	account: IAccount;

	data: {
		address: string;
		default?: boolean;
		private?: boolean;
	};
}

export interface IEmailsEditActionPayload {
	id: string;

	data: {
		default?: boolean;
		private?: boolean;
	};
}

export interface IEmailsSaveActionPayload {
	id: string;
}

export interface IEmailsRemoveActionPayload {
	id: string;
}

export interface IEmailsValidateActionPayload {
	address: string;
}

export interface IEmailsSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
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
	type: string;

	address: string;
	default: boolean;
	private: boolean;
	verified: boolean;

	// Relations
	relationshipNames: string[];

	account: IPlainRelation;
}
