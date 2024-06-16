import { TJsonaModel, TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';
import { _GettersTree } from 'pinia';

import { DeviceCategory } from '@fastybird/metadata-library';

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
	IPlainRelation,
} from '../../models/types';

// STORE
// =====

export interface IDevicesState {
	semaphore: IDevicesStateSemaphore;
	firstLoad: string[];
	data: { [key: string]: IDevice };
}

export interface IDevicesGetters extends _GettersTree<IDevicesState> {
	firstLoadFinished: (state: IDevicesState) => (connectorId?: string | null) => boolean;
	getting: (state: IDevicesState) => (id: string) => boolean;
	fetching: (state: IDevicesState) => (connectorId?: string | null) => boolean;
	findById: (state: IDevicesState) => (id: string) => IDevice | null;
	findForConnector: (state: IDevicesState) => (connectorId: string) => IDevice[];
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
	type: { source: string; type: string; entity: string };

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
	type: { source: string; type: string; entity?: string };

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
	properties?: (IPlainRelation | IDeviceControlResponseModel)[];

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
	type: { source: string; type: string; entity: string };

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
