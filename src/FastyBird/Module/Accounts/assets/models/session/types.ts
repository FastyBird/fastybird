import { TJsonaModel, TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';
import { _GettersTree } from 'pinia';

import { IAccount, IEntityMeta, IIdentity, IPlainRelation } from '../types';

export interface ISessionMeta extends IEntityMeta {
	entity: 'session';
}

// STORE
// =====

export interface ISessionState {
	semaphore: ISessionStateSemaphore;
	data: ISession;
}

export interface ISessionGetters extends _GettersTree<ISessionState> {
	accessToken: (state: ISessionState) => () => string | null;
	refreshToken: (state: ISessionState) => () => string | null;
	accountId: (state: ISessionState) => () => IAccount['id'] | null;
	account: (state: ISessionState) => () => IAccount | null;
	isSignedIn: (state: ISessionState) => () => boolean;
}

export interface ISessionActions {
	initialize: () => void;
	clear: () => void;
	fetch: () => Promise<boolean>;
	create: (payload: ISessionCreateActionPayload) => Promise<boolean>;
	refresh: () => Promise<boolean>;
}

// STORE STATE
// ===========

interface ISessionStateSemaphore {
	fetching: boolean;
	creating: boolean;
	updating: boolean;
}

export interface ISession {
	refreshToken: string | null;
	accessToken: string | null;
	tokenExpiration: string | null;
	tokenType: string;

	accountId: IAccount['id'] | null;
}

// STORE DATA FACTORIES
// ====================

export interface ISessionRecordFactoryPayload {
	accessToken: string;
	refreshToken: string;
	tokenType: string;
}

// STORE ACTIONS
// =============

export interface ISessionCreateActionPayload {
	uid: IIdentity['uid'];
	password: IIdentity['password'];
}

// API RESPONSES JSONS
// ===================

export interface ISessionResponseJson extends TJsonApiBody {
	data: ISessionResponseData;
}

interface ISessionResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: ISessionResponseDataAttributes;
	relationships: ISessionResponseDataRelationships;
}

interface ISessionResponseDataAttributes {
	expiration: string;
	refresh: string;
	token: string;
	token_type: string;
}

interface ISessionResponseDataRelationships extends TJsonApiRelationships {
	account: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface ISessionResponseModel extends TJsonaModel {
	id: string;
	type: ISessionMeta;

	token: string;
	tokenType: string;
	expiration: string;
	refresh: string;

	// Relations
	relationshipNames: string[];

	account: IPlainRelation;
}
