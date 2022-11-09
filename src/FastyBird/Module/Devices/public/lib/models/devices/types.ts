import { TJsonaModel, TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';
import { _GettersTree } from 'pinia';

import {
	IChannelResponseData,
	IChannelResponseModel,
	IConnector,
	IConnectorResponseModel,
	IDeviceAttributeResponseModel,
	IDeviceControlResponseData,
	IDeviceControlResponseModel,
	IDeviceProperty,
	IDevicePropertyResponseData,
	IDevicePropertyResponseModel,
	IPlainRelation,
} from '@/lib/models/types';

// STORE
// =====

export interface IDevicesState {
	semaphore: IDevicesStateSemaphore;
	firstLoad: boolean;
	data: { [key: string]: IDevice };
}

export interface IDevicesGetters extends _GettersTree<IDevicesState> {
	firstLoadFinished: (state: IDevicesState) => boolean;
	getting: (state: IDevicesState) => (id: string) => boolean;
	fetching: (state: IDevicesState) => boolean;
	findById: (state: IDevicesState) => (id: string) => IDevice | null;
	findForConnector: (state: IDevicesState) => (connectorId: string) => IDevice[];
}

export interface IDevicesActions {
	set: (payload: IDevicesSetActionPayload) => Promise<IDevice>;
	get: (payload: IDevicesGetActionPayload) => Promise<boolean>;
	fetch: (payload: IDevicesFetchActionPayload) => Promise<boolean>;
	add: (payload: IDevicesAddActionPayload) => Promise<IDevice>;
	edit: (payload: IDevicesEditActionPayload) => Promise<IDevice>;
	save: (payload: IDevicesSaveActionPayload) => Promise<IDevice>;
	remove: (payload: IDevicesRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IDevicesSocketDataActionPayload) => Promise<boolean>;
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
	items: boolean;
	item: string[];
}

export interface IDevice {
	id: string;
	type: { source: string; type: string; entity: string };

	draft: boolean;

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
	attributes: IPlainRelation[];

	connector: IPlainRelation;

	owner: string | null;

	// Entity transformers
	stateProperty: IDeviceProperty | null;
	hasComment: boolean;
}

// STORE DATA FACTORIES
// ====================

export interface IDeviceRecordFactoryPayload {
	id?: string;
	type: { source: string; type: string; entity?: string };

	identifier: string;
	name?: string | null;
	comment?: string | null;

	// Relations
	relationshipNames?: string[];

	parents?: (IPlainRelation | IDeviceResponseModel)[];
	children?: (IPlainRelation | IDeviceResponseModel)[];

	channels?: (IPlainRelation | IChannelResponseModel)[];
	controls?: (IPlainRelation | IDeviceControlResponseModel)[];
	properties?: (IPlainRelation | IDeviceControlResponseModel)[];
	attributes?: (IPlainRelation | IDeviceAttributeResponseModel)[];

	connectorId: string;

	owner?: string | null;
}

// STORE ACTIONS
// =============

export interface IDevicesSetActionPayload {
	data: IDeviceRecordFactoryPayload;
}

export interface IDevicesGetActionPayload {
	id: string;
	connector?: IConnector;
	withChannels?: boolean;
}

export interface IDevicesFetchActionPayload {
	connector?: IConnector;
	withChannels?: boolean;
}

export interface IDevicesAddActionPayload {
	id?: string;
	type: { source: string; type: string; entity?: string };

	draft?: boolean;

	connector: IConnector;

	parents?: IDevice[];

	data: {
		identifier: string;
		name?: string | null;
		comment?: string | null;
	};
}

export interface IDevicesEditActionPayload {
	id: string;

	data: {
		identifier?: string;
		name?: string | null;
		comment?: string | null;
	};
}

export interface IDevicesSaveActionPayload {
	id: string;
}

export interface IDevicesRemoveActionPayload {
	id: string;
}

export interface IDevicesSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
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
	identifier: string;
	name: string | null;
	comment: string | null;

	owner: string | null;
}

interface IDeviceResponseDataRelationships extends TJsonApiRelationships {
	properties: TJsonApiRelation;
	controls: TJsonApiRelation;
	attributes: TJsonApiRelation;
	channels: TJsonApiRelation;
	parents: TJsonApiRelation;
	children: TJsonApiRelation;
	connector: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IDeviceResponseModel extends TJsonaModel {
	id: string;
	type: { source: string; type: string; entity: string };

	identifier: string;
	name: string | null;
	comment: string | null;

	owner: string | null;

	// Relations
	relationshipNames: string[];

	properties: (IPlainRelation | IDevicePropertyResponseModel)[];
	controls: (IPlainRelation | IDeviceControlResponseModel)[];
	attributes: (IPlainRelation | IDeviceAttributeResponseModel)[];
	channels: (IPlainRelation | IChannelResponseModel)[];
	parents: (IPlainRelation | IDeviceResponseModel)[];
	children: (IPlainRelation | IDeviceResponseModel)[];
	connector: IPlainRelation | IConnectorResponseModel;
}
