import { TJsonaModel, TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';

import { IAccountResponseData, IPlainRelation } from '../../models/types';

// STORE STATE
// ===========

export interface IRolesState {
	semaphore: IRolesStateSemaphore;
	firstLoad: boolean;
	data: { [key: string]: IRole };
}

export interface IRolesStateSemaphore {
	fetching: IRolesStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

interface IRolesStateSemaphoreFetching {
	items: boolean;
	item: string[];
}

// STORE MODELS
// ============

export interface IRole {
	id: string;
	type: string;

	draft: boolean;

	name: string;
	description: string | null;

	anonymous: boolean;
	authenticated: boolean;
	administrator: boolean;

	// Relations
	relationshipNames: string[];

	accounts: IPlainRelation[];
}

// STORE DATA FACTORIES
// ====================

export interface IRoleRecordFactoryPayload {
	id?: string;
	type?: string;

	draft?: boolean;

	name: string;
	description?: string | null;

	anonymous?: boolean;
	authenticated?: boolean;
	administrator?: boolean;

	// Relations
	relationshipNames?: string[];
}

// STORE ACTIONS
// =============

export interface IRolesGetActionPayload {
	id: string;
}

export interface IRolesAddActionPayload {
	id?: string;
	type?: string;

	draft?: boolean;

	data: {
		name: string;
		description?: string | null;

		anonymous?: boolean;
		authenticated?: boolean;
		administrator?: boolean;
	};
}

export interface IRolesEditActionPayload {
	id: string;

	data: {
		name: string;
		description?: string | null;

		anonymous?: boolean;
		authenticated?: boolean;
		administrator?: boolean;
	};
}

export interface IRolesSaveActionPayload {
	id: string;
}

export interface IRolesRemoveActionPayload {
	id: string;
}

export interface IRolesSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

// API RESPONSES JSONS
// ===================

export interface IRoleResponseJson extends TJsonApiBody {
	data: IRoleResponseData;
	includes?: IAccountResponseData[];
}

export interface IRolesResponseJson extends TJsonApiBody {
	data: IRoleResponseData[];
	includes?: IAccountResponseData[];
}

export interface IRoleResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IRoleResponseDataAttributes;
	relationships: IRoleResponseDataRelationships;
}

interface IRoleResponseDataAttributes {
	name: string;
	description: string | null;
	anonymous: boolean;
	authenticated: boolean;
	administrator: boolean;
}

interface IRoleResponseDataRelationships extends TJsonApiRelationships {
	accounts: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IRoleResponseModel extends TJsonaModel {
	id: string;
	type: string;

	name: string;
	description: string | null;

	anonymous: boolean;
	authenticated: boolean;
	administrator: boolean;

	// Relations
	relationshipNames: string[];

	accounts: IPlainRelation[];
}
