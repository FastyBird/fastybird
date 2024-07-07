import { _GettersTree } from 'pinia';
import { TJsonaModel, TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';

import { IdentityState } from '@fastybird/metadata-library';

import { IAccount, IAccountResponseData, IEntityMeta, IPlainRelation } from '../../models/types';

export interface IIdentityMeta extends IEntityMeta {
	entity: 'identity';
}

// STORE
// =====

export interface IIdentitiesState {
	semaphore: IIdentitiesStateSemaphore;
	firstLoad: IIdentity['id'][];
	data: { [key: IIdentity['id']]: IIdentity };
}

export interface IIdentitiesGetters extends _GettersTree<IIdentitiesState> {
	firstLoadFinished: (state: IIdentitiesState) => (accountId: IAccount['id']) => boolean;
	getting: (state: IIdentitiesState) => (id: IIdentity['id']) => boolean;
	fetching: (state: IIdentitiesState) => (accountId: IAccount['id'] | null) => boolean;
	findById: (state: IIdentitiesState) => (id: IIdentity['id']) => IIdentity | null;
	findForAccount: (state: IIdentitiesState) => (accountId: IAccount['id']) => IIdentity[];
}

export interface IIdentitiesActions {
	set: (payload: IIdentitiesSetActionPayload) => Promise<IIdentity>;
	unset: (payload: IIdentitiesUnsetActionPayload) => void;
	get: (payload: IIdentitiesGetActionPayload) => Promise<boolean>;
	fetch: (payload: IIdentitiesFetchActionPayload) => Promise<boolean>;
	add: (payload: IIdentitiesAddActionPayload) => Promise<IIdentity>;
	edit: (payload: IIdentitiesEditActionPayload) => Promise<IIdentity>;
	save: (payload: IIdentitiesSaveActionPayload) => Promise<IIdentity>;
	remove: (payload: IIdentitiesRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IIdentitiesSocketDataActionPayload) => Promise<boolean>;
}

// STORE STATE
// ===========

export interface IIdentitiesStateSemaphore {
	fetching: IIdentitiesStateSemaphoreFetching;
	creating: IIdentity['id'][];
	updating: IIdentity['id'][];
	deleting: IIdentity['id'][];
}

interface IIdentitiesStateSemaphoreFetching {
	items: IAccount['id'][];
	item: IIdentity['id'][];
}

// STORE MODELS
// ============

export interface IIdentity {
	id: string;
	type: IIdentityMeta;

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
	id?: IIdentity['id'];
	type: IIdentityMeta;

	draft?: IIdentity['draft'];

	state?: IIdentity['state'];

	uid: IIdentity['uid'];
	password?: IIdentity['password'];

	// Relations
	relationshipNames?: IIdentity['relationshipNames'];

	accountId: IAccount['id'];
}

// STORE ACTIONS
// =============

export interface IIdentitiesSetActionPayload {
	data: IIdentityRecordFactoryPayload;
}

export interface IIdentitiesUnsetActionPayload {
	account?: IAccount;
	id?: IIdentity['id'];
}

export interface IIdentitiesGetActionPayload {
	account: IAccount;
	id: IIdentity['id'];
}

export interface IIdentitiesFetchActionPayload {
	account: IAccount;
}

export interface IIdentitiesAddActionPayload {
	id?: IIdentity['id'];
	type: IIdentityMeta;

	draft?: IIdentity['draft'];

	account: IIdentity['account'];

	data: {
		uid: IIdentity['uid'];
		password: IIdentity['password'];
	};
}

export interface IIdentitiesEditActionPayload {
	id: IIdentity['id'];

	data: {
		password: {
			current: IIdentity['password'];
			new: IIdentity['password'];
		};
	};
}

export interface IIdentitiesSaveActionPayload {
	id: IIdentity['id'];
}

export interface IIdentitiesRemoveActionPayload {
	id: IIdentity['id'];
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
	type: IIdentityMeta;

	state: IdentityState;

	uid: string;
	password?: string;

	// Relations
	relationshipNames: string[];

	account: IPlainRelation;
}
