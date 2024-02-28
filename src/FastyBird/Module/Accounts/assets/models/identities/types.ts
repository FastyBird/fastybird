import { TJsonaModel, TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';

import { IdentityState } from '@fastybird/metadata-library';

import { IAccount, IAccountResponseData, IPlainRelation } from '../../models/types';

// STORE STATE
// ===========

export interface IIdentitiesState {
	semaphore: IIdentitiesStateSemaphore;
	firstLoad: string[];
	data: { [key: string]: IIdentity };
}

export interface IIdentitiesStateSemaphore {
	fetching: IIdentitiesStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

interface IIdentitiesStateSemaphoreFetching {
	items: string[];
	item: string[];
}

// STORE MODELS
// ============

export interface IIdentity {
	id: string;
	type: string;

	draft: boolean;

	state: IdentityState;

	uid: string;
	password?: string;

	// Relations
	relationshipNames: string[];

	account: IPlainRelation;
}

// STORE DATA FACTORIES
// ====================

export interface IIdentityRecordFactoryPayload {
	id?: string;
	type?: string;

	draft?: boolean;

	state?: IdentityState;

	uid: string;
	password?: string;

	// Relations
	relationshipNames?: string[];

	accountId: string;
}

// STORE ACTIONS
// =============

export interface IIdentitiesSetActionPayload {
	data: IIdentityRecordFactoryPayload;
}

export interface IIdentitiesUnsetActionPayload {
	account?: IAccount;
	id?: string;
}

export interface IIdentitiesGetActionPayload {
	account: IAccount;
	id: string;
}

export interface IIdentitiesFetchActionPayload {
	account: IAccount;
}

export interface IIdentitiesAddActionPayload {
	id?: string;
	type?: string;

	draft?: boolean;

	account: IAccount;

	data: {
		uid: string;
		password: string;
	};
}

export interface IIdentitiesEditActionPayload {
	id: string;

	data: {
		password: {
			current: string;
			new: string;
		};
	};
}

export interface IIdentitiesSaveActionPayload {
	id: string;
}

export interface IIdentitiesRemoveActionPayload {
	id: string;
}

export interface IIdentitiesSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

// API RESPONSES
// =============

export interface IIdentityResponseJson extends TJsonApiBody {
	data: IIdentityResponseData;
	includes?: IAccountResponseData[];
}

export interface IIdentitiesResponseJson extends TJsonApiBody {
	data: IIdentityResponseData[];
	includes?: IAccountResponseData[];
}

export interface IIdentityResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IIdentityResponseDataAttributes;
	relationships: IIdentityResponseDataRelationships;
}

interface IIdentityResponseDataAttributes {
	uid: string;
	state: IdentityState;
}

interface IIdentityResponseDataRelationships extends TJsonApiRelationships {
	account: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IIdentityResponseModel extends TJsonaModel {
	id: string;
	type: string;

	state: IdentityState;

	uid: string;
	password?: string;

	// Relations
	relationshipNames: string[];

	account: IPlainRelation;
}
