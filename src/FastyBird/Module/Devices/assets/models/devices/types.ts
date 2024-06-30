import { TJsonaModel, TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';
import { _GettersTree } from 'pinia';

import { DeviceCategory, DeviceDocument } from '@fastybird/metadata-library';

import {
	IChannelResponseData,
	IChannelResponseModel,
	IConnector,
	IConnectorResponseModel,
	IDeviceControlResponseData,
	IDeviceControlResponseModel,
	IDeviceProperty,
	IDevicePropertyResponseData,
	IDevicePropertyResponseModel,
	IEntityMeta,
	IPlainRelation,
} from '../../models/types';

export interface IDeviceMeta extends IEntityMeta {
	entity: 'device';
}

// STORE
// =====

export interface IDevicesState {
	semaphore: IDevicesStateSemaphore;
	firstLoad: IConnector['id'][];
	data: { [key: IDevice['id']]: IDevice } | undefined;
	meta: { [key: IDevice['id']]: IDeviceMeta };
}

export interface IDevicesGetters extends _GettersTree<IDevicesState> {
	firstLoadFinished: (state: IDevicesState) => (connectorId?: IConnector['id'] | null) => boolean;
	getting: (state: IDevicesState) => (id: IDevice['id']) => boolean;
	fetching: (state: IDevicesState) => (connectorId?: IConnector['id'] | null) => boolean;
	findById: (state: IDevicesState) => (id: IDevice['id']) => IDevice | null;
	findForConnector: (state: IDevicesState) => (connectorId: IConnector['id']) => IDevice[];
	findAll: (state: IDevicesState) => () => IDevice[];
	findMeta: (state: IDevicesState) => (id: IDevice['id']) => IDeviceMeta | null;
}

export interface IDevicesActions {
	set: (payload: IDevicesSetActionPayload) => Promise<IDevice>;
	get: (payload: IDevicesGetActionPayload) => Promise<boolean>;
	fetch: (payload?: IDevicesFetchActionPayload) => Promise<boolean>;
	add: (payload: IDevicesAddActionPayload) => Promise<IDevice>;
	edit: (payload: IDevicesEditActionPayload) => Promise<IDevice>;
	save: (payload: IDevicesSaveActionPayload) => Promise<IDevice>;
	remove: (payload: IDevicesRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IDevicesSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IDevicesInsertDataActionPayload) => Promise<boolean>;
	loadRecord: (payload: IDevicesLoadRecordActionPayload) => Promise<boolean>;
	loadAllRecords: (payload?: IDevicesLoadAllRecordsActionPayload) => Promise<boolean>;
}

// STORE STATE
// ===========

interface IDevicesStateSemaphore {
	fetching: IDevicesStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

interface IDevicesStateSemaphoreFetching {
	items: string[];
	item: string[];
}

export interface IDevice {
	id: string;
	type: IDeviceMeta;

	draft: boolean;

	category: DeviceCategory;
	identifier: string;
	name: string | null;
	comment: string | null;

	// Relations
	relationshipNames: string[];

	parents: IPlainRelation[];
	children: IPlainRelation[];

	channels: IPlainRelation[];
	controls: IPlainRelation[];
	properties: IPlainRelation[];

	connector: IPlainRelation;

	owner: string | null;

	// Transformer transformers
	stateProperty: IDeviceProperty | null;
	hasComment: boolean;
}

// STORE DATA FACTORIES
// ====================

export interface IDeviceRecordFactoryPayload {
	id?: string;
	type: IDeviceMeta;

	category: DeviceCategory;
	identifier: string;
	name?: string | null;
	comment?: string | null;

	// Relations
	relationshipNames?: string[];

	parents?: (IPlainRelation | IDeviceResponseModel)[];
	children?: (IPlainRelation | IDeviceResponseModel)[];

	channels?: (IPlainRelation | IChannelResponseModel)[];
	controls?: (IPlainRelation | IDeviceControlResponseModel)[];
	properties?: (IPlainRelation | IDevicePropertyResponseModel)[];

	connectorId?: string;
	connector?: IPlainRelation;

	owner?: string | null;
}

// STORE ACTIONS
// =============

export interface IDevicesSetActionPayload {
	data: IDeviceRecordFactoryPayload;
}

export interface IDevicesGetActionPayload {
	id: IDevice['id'];
	connectorId?: IConnector['id'];
	refresh?: boolean;
}

export interface IDevicesFetchActionPayload {
	connectorId?: IConnector['id'];
	refresh?: boolean;
}

export interface IDevicesAddActionPayload {
	id?: IDevice['id'];
	type: IDeviceMeta;

	draft?: IDevice['draft'];

	connector: IConnector;

	parents?: IDevice[];

	data: {
		identifier: IDevice['identifier'];
		name?: IDevice['name'];
		comment?: IDevice['comment'];
	};
}

export interface IDevicesEditActionPayload {
	id: IDevice['id'];

	data: {
		identifier?: IDevice['identifier'];
		name?: IDevice['name'];
		comment?: IDevice['comment'];
	};
}

export interface IDevicesSaveActionPayload {
	id: IDevice['id'];
}

export interface IDevicesRemoveActionPayload {
	id: IDevice['id'];
}

export interface IDevicesSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IDevicesInsertDataActionPayload {
	data: DeviceDocument | DeviceDocument[];
}

export interface IDevicesLoadRecordActionPayload {
	id: IDevice['id'];
}

export interface IDevicesLoadAllRecordsActionPayload {
	connectorId?: IConnector['id'];
}

// API RESPONSES JSONS
// ===================

export interface IDeviceResponseJson extends TJsonApiBody {
	data: IDeviceResponseData;
	included?: (IDevicePropertyResponseData | IDeviceControlResponseData | IChannelResponseData)[];
}

export interface IDevicesResponseJson extends TJsonApiBody {
	data: IDeviceResponseData[];
	included?: (IDevicePropertyResponseData | IDeviceControlResponseData | IChannelResponseData)[];
}

export interface IDeviceResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IDeviceResponseDataAttributes;
	relationships: IDeviceResponseDataRelationships;
}

interface IDeviceResponseDataAttributes {
	category: DeviceCategory;
	identifier: string;
	name: string | null;
	comment: string | null;

	owner: string | null;
}

interface IDeviceResponseDataRelationships extends TJsonApiRelationships {
	properties: TJsonApiRelation;
	controls: TJsonApiRelation;
	channels: TJsonApiRelation;
	parents: TJsonApiRelation;
	children: TJsonApiRelation;
	connector: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IDeviceResponseModel extends TJsonaModel {
	id: string;
	type: IDeviceMeta;

	category: DeviceCategory;
	identifier: string;
	name: string | null;
	comment: string | null;

	owner: string | null;

	// Relations
	relationshipNames: string[];

	properties: (IPlainRelation | IDevicePropertyResponseModel)[];
	controls: (IPlainRelation | IDeviceControlResponseModel)[];
	channels: (IPlainRelation | IChannelResponseModel)[];
	parents: (IPlainRelation | IDeviceResponseModel)[];
	children: (IPlainRelation | IDeviceResponseModel)[];
	connector: IPlainRelation | IConnectorResponseModel;
}

// DATABASE
// ========

export interface IDeviceDatabaseRecord {
	id: string;
	type: IDeviceMeta;

	category: DeviceCategory;
	identifier: string;
	name: string | null;
	comment: string | null;

	// Relations
	relationshipNames: string[];

	parents: IPlainRelation[];
	children: IPlainRelation[];

	channels: IPlainRelation[];
	controls: IPlainRelation[];
	properties: IPlainRelation[];

	connector: IPlainRelation;

	owner: string | null;
}
