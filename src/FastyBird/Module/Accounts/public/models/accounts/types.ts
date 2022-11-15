import { TJsonaModel, TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';

import { AccountState } from '@fastybird/metadata-library';

import { IEmail, IEmailResponseData, IEmailResponseModel, IIdentityResponseData, IIdentityResponseModel, IPlainRelation } from '@/types';

// STORE STATE
// ===========

export interface IAccountsState {
	semaphore: IAccountsStateSemaphore;
	firstLoad: boolean;
	data: { [key: string]: IAccount };
}

interface IAccountsStateSemaphore {
	fetching: IAccountsStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

interface IAccountsStateSemaphoreFetching {
	items: boolean;
	item: string[];
}

export interface IAccount {
	id: string;
	type: string;

	draft: boolean;

	details: {
		firstName: string;
		lastName: string;
		middleName: string | null;
	};

	language: string;

	weekStart: number;
	dateTime: {
		timezone: string;
		dateFormat: string;
		timeFormat: string;
	};

	state: AccountState;

	lastVisit: string | null;
	registered: string | null;

	// Relations
	relationshipNames: string[];

	emails: IPlainRelation[];
	identities: IPlainRelation[];
	roles: IPlainRelation[];

	// Entity transformers
	name: string;
	email?: IEmail | null;
}

// STORE DATA FACTORIES
// ====================

export interface IAccountRecordFactoryPayload {
	id?: string;
	type?: string;

	details: {
		firstName: string;
		lastName: string;
		middleName?: string | null;
	};

	language?: string;

	weekStart?: number;
	dateTime?: {
		timezone?: string;
		dateFormat?: string;
		timeFormat?: string;
	};

	state?: AccountState;

	lastVisit?: string | null;
	registered?: string | null;

	// Relations
	relationshipNames?: string[];

	emails?: IEmailResponseModel[];
	identities?: IIdentityResponseModel[];
	roles?: IPlainRelation[];
}

// STORE ACTIONS
// =============

export interface IAccountsSetActionPayload {
	data: IAccountRecordFactoryPayload;
}

export interface IAccountsGetActionPayload {
	id: string;
}

export interface IAccountsAddActionPayload {
	id?: string;
	type?: string;

	draft?: boolean;

	data: {
		details: {
			firstName: string;
			lastName: string;
			middleName?: string | null;
		};

		language?: string;

		weekStart?: number;
		dateTime: {
			timezone?: string;
			dateFormat?: string;
			timeFormat?: string;
		};
	};
}

export interface IAccountsEditActionPayload {
	id: string;

	data: {
		details: {
			firstName: string;
			lastName: string;
			middleName?: string | null;
		};

		language?: string;

		weekStart?: number;
		dateTime: {
			timezone?: string;
			dateFormat?: string;
			timeFormat?: string;
		};
	};
}

export interface IAccountsSaveActionPayload {
	id: string;
}

export interface IAccountsRemoveActionPayload {
	id: string;
}

export interface IAccountsSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

// API RESPONSES JSONS
// ===================

export interface IAccountResponseJson extends TJsonApiBody {
	data: IAccountResponseData;
	included?: (IEmailResponseData | IIdentityResponseData)[];
}

export interface IAccountsResponseJson extends TJsonApiBody {
	data: IAccountResponseData[];
	included?: (IEmailResponseData | IIdentityResponseData)[];
}

export interface IAccountResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IAccountResponseDataAttributes;
	relationships: IAccountResponseDataRelationships;
}

interface IAccountResponseDataAttributes {
	details: IAccountResponseDataAttributesDetails;

	week_start: number;
	datetime: IAccountResponseDataAttributesDatetime;

	language: string;

	state: AccountState;

	last_visit: string | null;
	registered: string | null;
}

interface IAccountResponseDataAttributesDetails {
	first_name: string;
	last_name: string;
	middle_name: string | null;
}

interface IAccountResponseDataAttributesDatetime {
	timezone: string;
	date_format: string;
	time_format: string;
}

interface IAccountResponseDataRelationships extends TJsonApiRelationships {
	emails: TJsonApiRelation;
	identities: TJsonApiRelation;
	roles: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IAccountResponseModel extends TJsonaModel {
	id: string;
	type: string;

	details: {
		firstName: string;
		lastName: string;
		middleName: string | null;
	};

	language: string;

	weekStart: number;
	dateTime: {
		timezone: string;
		dateFormat: string;
		timeFormat: string;
	};

	state: AccountState;

	lastVisit: string | null;
	registered: string | null;

	// Relations
	relationshipNames: string[];

	emails: IEmailResponseModel[];
	identities: IIdentityResponseModel[];
	roles: IPlainRelation[];
}
