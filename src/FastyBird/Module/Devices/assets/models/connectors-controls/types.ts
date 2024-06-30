import { ConnectorControlDocument } from '@fastybird/metadata-library';
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
	IControlDatabaseRecord,
	IControlMeta,
} from '../../models/types';

export interface IConnectorControlMeta extends IControlMeta {
	parent: 'connector';
}

// STORE
// =====

export interface IConnectorControlsState {
	semaphore: IConnectorControlsStateSemaphore;
	data: { [key: IConnectorControl['id']]: IConnectorControl } | undefined;
	meta: { [key: IConnectorControl['id']]: IConnectorControlMeta };
}

export interface IConnectorControlsGetters extends _GettersTree<IConnectorControlsState> {
	getting: (state: IConnectorControlsState) => (id: IConnectorControl['id']) => boolean;
	fetching: (state: IConnectorControlsState) => (connectorId: IConnector['id'] | null) => boolean;
	findById: (state: IConnectorControlsState) => (id: IConnectorControl['id']) => IConnectorControl | null;
	findByName: (state: IConnectorControlsState) => (connector: IConnector, name: IConnectorControl['name']) => IConnectorControl | null;
	findForConnector: (state: IConnectorControlsState) => (connectorId: IConnector['id']) => IConnectorControl[];
	findMeta: (state: IConnectorControlsState) => (id: IConnectorControl['id']) => IConnectorControlMeta | null;
}

export interface IConnectorControlsActions {
	set: (payload: IConnectorControlsSetActionPayload) => Promise<IConnectorControl>;
	unset: (payload: IConnectorControlsUnsetActionPayload) => void;
	get: (payload: IConnectorControlsGetActionPayload) => Promise<boolean>;
	fetch: (payload: IConnectorControlsFetchActionPayload) => Promise<boolean>;
	add: (payload: IConnectorControlsAddActionPayload) => Promise<IConnectorControl>;
	save: (payload: IConnectorControlsSaveActionPayload) => Promise<IConnectorControl>;
	remove: (payload: IConnectorControlsRemoveActionPayload) => Promise<boolean>;
	transmitCommand: (payload: IConnectorControlsTransmitCommandActionPayload) => Promise<boolean>;
	socketData: (payload: IConnectorControlsSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IConnectorControlsInsertDataActionPayload) => Promise<boolean>;
	loadRecord: (payload: IConnectorControlsLoadRecordActionPayload) => Promise<boolean>;
	loadAllRecords: (payload?: IConnectorControlsLoadAllRecordsActionPayload) => Promise<boolean>;
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
	type: IConnectorControlMeta;

	// Relations
	connector: IPlainRelation;
}

// STORE DATA FACTORIES
// ====================

export interface IConnectorControlRecordFactoryPayload extends IControlRecordFactoryPayload {
	type: IConnectorControlMeta;

	// Relations
	connectorId?: string;
	connector?: IPlainRelation;
}

// STORE ACTIONS
// =============

export interface IConnectorControlsSetActionPayload {
	data: IConnectorControlRecordFactoryPayload;
}

export interface IConnectorControlsUnsetActionPayload {
	connector?: IConnector;
	id?: IConnectorControl['id'];
}

export interface IConnectorControlsGetActionPayload {
	connector: IConnector;
	id: IConnectorControl['id'];
	refresh?: boolean;
}

export interface IConnectorControlsFetchActionPayload {
	connector: IConnector;
	refresh?: boolean;
}

export interface IConnectorControlsAddActionPayload extends IControlsAddActionPayload {
	type: IConnectorControlMeta;
	connector: IConnector;
}

export interface IConnectorControlsSaveActionPayload {
	id: IConnectorControl['id'];
}

export interface IConnectorControlsRemoveActionPayload {
	id: IConnectorControl['id'];
}

export interface IConnectorControlsTransmitCommandActionPayload {
	id: IConnectorControl['id'];
	value?: string;
}

export interface IConnectorControlsSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IConnectorControlsInsertDataActionPayload {
	data: ConnectorControlDocument | ConnectorControlDocument[];
}

export interface IConnectorControlsLoadRecordActionPayload {
	id: IConnectorControl['id'];
}

export interface IConnectorControlsLoadAllRecordsActionPayload {
	connector: IConnector;
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
	type: IConnectorControlMeta;

	// Relations
	connector: IPlainRelation | IConnectorResponseModel;
}

// DATABASE
// ========

export interface IConnectorControlDatabaseRecord extends IControlDatabaseRecord {
	type: IConnectorControlMeta;

	// Relations
	connector: IPlainRelation;
}
