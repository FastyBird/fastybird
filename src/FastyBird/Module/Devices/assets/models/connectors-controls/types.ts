import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';
import { _GettersTree } from 'pinia';

import {
	IConnector,
	IConnectorResponseData,
	IPlainRelation,
	IControlsAddActionPayload,
	IControl,
	IControlRecordFactoryPayload,
	IConnectorResponseModel,
	IControlResponseModel,
} from '../../models/types';

// STORE
// =====

export interface IConnectorControlsState {
	semaphore: IConnectorControlsStateSemaphore;
	firstLoad: string[];
	data: { [key: string]: IConnectorControl };
}

export interface IConnectorControlsGetters extends _GettersTree<IConnectorControlsState> {
	firstLoadFinished: (state: IConnectorControlsState) => (connectorId: string) => boolean;
	getting: (state: IConnectorControlsState) => (controlId: string) => boolean;
	fetching: (state: IConnectorControlsState) => (connectorId: string | null) => boolean;
	findById: (state: IConnectorControlsState) => (id: string) => IConnectorControl | null;
	findByName: (state: IConnectorControlsState) => (connector: IConnector, name: string) => IConnectorControl | null;
	findForConnector: (state: IConnectorControlsState) => (connectorId: string) => IConnectorControl[];
}

export interface IConnectorControlsActions {
	set: (payload: IConnectorControlsSetActionPayload) => Promise<IConnectorControl>;
	unset: (payload: IConnectorControlsUnsetActionPayload) => void;
	get: (payload: IConnectorControlsGetActionPayload) => Promise<boolean>;
	fetch: (payload: IConnectorControlsFetchActionPayload) => Promise<boolean>;
	add: (payload: IConnectorControlsAddActionPayload) => Promise<IConnectorControl>;
	save: (payload: IConnectorControlsSaveActionPayload) => Promise<IConnectorControl>;
	remove: (payload: IConnectorControlsRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IConnectorControlsSocketDataActionPayload) => Promise<boolean>;
}

// STORE STATE
// ===========

export interface IConnectorControlsStateSemaphore {
	fetching: IConnectorControlsStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

interface IConnectorControlsStateSemaphoreFetching {
	items: string[];
	item: string[];
}

// STORE MODELS
// ============

export interface IConnectorControl extends IControl {
	// Relations
	connector: IPlainRelation;
}

// STORE DATA FACTORIES
// ====================

export interface IConnectorControlRecordFactoryPayload extends IControlRecordFactoryPayload {
	// Relations
	connectorId: string;
}

// STORE ACTIONS
// =============

export interface IConnectorControlsSetActionPayload {
	data: IConnectorControlRecordFactoryPayload;
}

export interface IConnectorControlsUnsetActionPayload {
	connector?: IConnector;
	id?: string;
}

export interface IConnectorControlsGetActionPayload {
	connector: IConnector;
	id: string;
}

export interface IConnectorControlsFetchActionPayload {
	connector: IConnector;
}

export interface IConnectorControlsAddActionPayload extends IControlsAddActionPayload {
	connector: IConnector;
}

export interface IConnectorControlsSaveActionPayload {
	id: string;
}

export interface IConnectorControlsRemoveActionPayload {
	id: string;
}

export interface IConnectorControlsSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

// API RESPONSES JSONS
// ===================

export interface IConnectorControlResponseJson extends TJsonApiBody {
	data: IConnectorControlResponseData;
	includes?: IConnectorResponseData[];
}

export interface IConnectorControlsResponseJson extends TJsonApiBody {
	data: IConnectorControlResponseData[];
	includes?: IConnectorResponseData[];
}

export interface IConnectorControlResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IConnectorControlResponseDataAttributes;
	relationships: IConnectorControlResponseDataRelationships;
}

interface IConnectorControlResponseDataAttributes {
	name: string | null;
}

interface IConnectorControlResponseDataRelationships extends TJsonApiRelationships {
	connector: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IConnectorControlResponseModel extends IControlResponseModel {
	// Relations
	connector: IPlainRelation | IConnectorResponseModel;
}
