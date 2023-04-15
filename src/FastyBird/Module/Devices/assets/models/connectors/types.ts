import { TJsonaModel, TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';
import { _GettersTree } from 'pinia';

import { ConnectorCategory } from '@fastybird/metadata-library';

import {
	IConnectorControlResponseData,
	IConnectorControlResponseModel,
	IConnectorProperty,
	IConnectorPropertyResponseData,
	IConnectorPropertyResponseModel,
	IDeviceResponseData,
	IDeviceResponseModel,
	IPlainRelation,
} from '@/models/types';

// STORE
// =====

export interface IConnectorsState {
	semaphore: IConnectorsStateSemaphore;
	firstLoad: boolean;
	data: { [key: string]: IConnector };
}

export interface IConnectorsGetters extends _GettersTree<IConnectorsState> {
	firstLoadFinished: (state: IConnectorsState) => boolean;
	getting: (state: IConnectorsState) => (id: string) => boolean;
	fetching: (state: IConnectorsState) => boolean;
	findById: (state: IConnectorsState) => (id: string) => IConnector | null;
}

export interface IConnectorsActions {
	set: (payload: IConnectorsSetActionPayload) => Promise<IConnector>;
	get: (payload: IConnectorsGetActionPayload) => Promise<boolean>;
	fetch: (payload: IConnectorsFetchActionPayload) => Promise<boolean>;
	add: (payload: IConnectorsAddActionPayload) => Promise<IConnector>;
	edit: (payload: IConnectorsEditActionPayload) => Promise<IConnector>;
	save: (payload: IConnectorsSaveActionPayload) => Promise<IConnector>;
	remove: (payload: IConnectorsRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IConnectorsSocketDataActionPayload) => Promise<boolean>;
}

// STORE STATE
// ===========

interface IConnectorsStateSemaphore {
	fetching: IConnectorsStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

interface IConnectorsStateSemaphoreFetching {
	items: boolean;
	item: string[];
}

export interface IConnector {
	id: string;
	type: { source: string; type: string; entity: string };

	draft: boolean;

	category: ConnectorCategory;
	identifier: string;
	name: string | null;
	comment: string | null;
	enabled: boolean;

	// Relations
	relationshipNames: string[];

	devices: IPlainRelation[];
	controls: IPlainRelation[];
	properties: IPlainRelation[];

	owner: string | null;

	// Transformer transformers
	isEnabled: boolean;
	stateProperty: IConnectorProperty | null;
	hasComment: boolean;
}

// STORE DATA FACTORIES
// ====================

export interface IConnectorRecordFactoryPayload {
	id?: string;
	type: { source: string; type: string; entity?: string };

	category: ConnectorCategory;
	identifier: string;
	name?: string | null;
	comment?: string | null;
	enabled?: boolean;

	// Relations
	relationshipNames?: string[];

	devices?: (IPlainRelation | IDeviceResponseModel)[];
	controls?: (IPlainRelation | IConnectorControlResponseModel)[];
	properties?: (IPlainRelation | IConnectorPropertyResponseModel)[];

	owner?: string | null;
}

// STORE ACTIONS
// =============

export interface IConnectorsSetActionPayload {
	data: IConnectorRecordFactoryPayload;
}

export interface IConnectorsGetActionPayload {
	id: string;
	withDevices?: boolean;
}

export interface IConnectorsFetchActionPayload {
	withDevices?: boolean;
}

export interface IConnectorsAddActionPayload {
	id?: string;
	type: { source: string; type: string; entity?: string };

	draft?: boolean;

	data: {
		identifier: string;
		name?: string | null;
		comment?: string | null;
		enabled?: boolean;
	};
}

export interface IConnectorsEditActionPayload {
	id: string;

	data: {
		name?: string | null;
		comment?: string | null;
		enabled?: boolean;
	};
}

export interface IConnectorsSaveActionPayload {
	id: string;
}

export interface IConnectorsRemoveActionPayload {
	id: string;
}

export interface IConnectorsSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

// API RESPONSES JSONS
// ===================

export interface IConnectorResponseJson extends TJsonApiBody {
	data: IConnectorResponseData;
	included?: (IConnectorPropertyResponseData | IConnectorControlResponseData | IDeviceResponseData)[];
}

export interface IConnectorsResponseJson extends TJsonApiBody {
	data: IConnectorResponseData[];
	included?: (IConnectorPropertyResponseData | IConnectorControlResponseData | IDeviceResponseData)[];
}

export interface IConnectorResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IConnectorResponseDataAttributes;
	relationships: IConnectorResponseDataRelationships;
}

interface IConnectorResponseDataAttributes {
	category: ConnectorCategory;
	identifier: string;
	name: string | null;
	comment: string | null;

	enabled: boolean;

	owner: string | null;
}

interface IConnectorResponseDataRelationships extends TJsonApiRelationships {
	properties: TJsonApiRelation;
	controls: TJsonApiRelation;
	devices: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IConnectorResponseModel extends TJsonaModel {
	id: string;
	type: { source: string; type: string; entity: string };

	category: ConnectorCategory;
	identifier: string;
	name: string | null;
	comment: string | null;

	enabled: boolean;

	owner: string | null;

	// Relations
	properties: (IPlainRelation | IConnectorPropertyResponseModel)[];
	controls: (IPlainRelation | IConnectorControlResponseModel)[];
	devices: (IPlainRelation | IDeviceResponseModel)[];
}
