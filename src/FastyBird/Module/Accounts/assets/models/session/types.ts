import { TJsonaModel, TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';
import { IPlainRelation } from '../types';

// STORE STATE
// ===========

export interface ISessionState {
	semaphore: ISessionStateSemaphore;
	data: ISession;
}

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

	accountId: string | null;
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
	uid: string;
	password: string;
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
	type: string;

	token: string;
	tokenType: string;
	expiration: string;
	refresh: string;

	// Relations
	relationshipNames: string[];

	account: IPlainRelation;
}
