import { TJsonaModel, TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';
import { _GettersTree } from 'pinia';

import {
	IChannelControlResponseData,
	IChannelControlResponseModel,
	IChannelPropertyResponseData,
	IChannelPropertyResponseModel,
	IDevice,
	IDeviceResponseData,
	IDeviceResponseModel,
	IPlainRelation,
} from '@/lib/models/types';

// STORE
// =====

export interface IChannelsState {
	semaphore: IChannelsStateSemaphore;
	firstLoad: string[];
	data: { [key: string]: IChannel };
}

export interface IChannelsGetters extends _GettersTree<IChannelsState> {
	firstLoadFinished: (state: IChannelsState) => (deviceId: string) => boolean;
	getting: (state: IChannelsState) => (channelId: string) => boolean;
	fetching: (state: IChannelsState) => (deviceId: string | null) => boolean;
	findById: (state: IChannelsState) => (id: string) => IChannel | null;
	findForDevice: (state: IChannelsState) => (deviceId: string) => IChannel[];
}

export interface IChannelsActions {
	set: (payload: IChannelsSetActionPayload) => Promise<IChannel>;
	unset: (payload: IChannelsUnsetActionPayload) => void;
	get: (payload: IChannelsGetActionPayload) => Promise<boolean>;
	fetch: (payload: IChannelsFetchActionPayload) => Promise<boolean>;
	add: (payload: IChannelsAddActionPayload) => Promise<IChannel>;
	edit: (payload: IChannelsEditActionPayload) => Promise<IChannel>;
	save: (payload: IChannelsSaveActionPayload) => Promise<IChannel>;
	remove: (payload: IChannelsRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IChannelsSocketDataActionPayload) => Promise<boolean>;
}

// STORE STATE
// ===========

interface IChannelsStateSemaphore {
	fetching: IChannelsStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

interface IChannelsStateSemaphoreFetching {
	items: string[];
	item: string[];
}

export interface IChannel {
	id: string;
	type: { source: string; entity: string };

	draft: boolean;

	identifier: string;
	name: string | null;
	comment: string | null;

	// Relations
	relationshipNames: string[];

	controls: IPlainRelation[];
	properties: IPlainRelation[];

	device: IPlainRelation;

	// Entity transformers
	hasComment: boolean;
}

// STORE DATA FACTORIES
// ====================

export interface IChannelRecordFactoryPayload {
	id?: string;
	type: { source: string; entity?: string };

	identifier: string;
	name?: string | null;
	comment?: string | null;

	// Relations
	relationshipNames?: string[];

	controls?: (IPlainRelation | IChannelControlResponseModel)[];
	properties?: (IPlainRelation | IChannelPropertyResponseModel)[];

	deviceId: string;
}

// STORE ACTIONS
// =============

export interface IChannelsSetActionPayload {
	data: IChannelRecordFactoryPayload;
}

export interface IChannelsUnsetActionPayload {
	device?: IDevice;
	id?: string;
}

export interface IChannelsGetActionPayload {
	device: IDevice;
	id: string;
}

export interface IChannelsFetchActionPayload {
	device: IDevice;
}

export interface IChannelsAddActionPayload {
	id?: string;
	type: { source: string; entity?: string };

	draft?: boolean;

	device: IDevice;

	data: {
		identifier: string;
		name?: string | null;
		comment?: string | null;
	};
}

export interface IChannelsEditActionPayload {
	id: string;

	data: {
		identifier?: string;
		name?: string | null;
		comment?: string | null;
	};
}

export interface IChannelsSaveActionPayload {
	id: string;
}

export interface IChannelsRemoveActionPayload {
	id: string;
}

export interface IChannelsSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

// API RESPONSES JSONS
// ===================

export interface IChannelResponseJson extends TJsonApiBody {
	data: IChannelResponseData;
	included?: (IChannelPropertyResponseData | IChannelControlResponseData | IDeviceResponseData)[];
}

export interface IChannelsResponseJson extends TJsonApiBody {
	data: IChannelResponseData[];
	included?: (IChannelPropertyResponseData | IChannelControlResponseData | IDeviceResponseData)[];
}

export interface IChannelResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IChannelResponseDataAttributes;
	relationships: IChannelResponseDataRelationships;
}

interface IChannelResponseDataAttributes {
	identifier: string;
	name: string | null;
	comment: string | null;
}

interface IChannelResponseDataRelationships extends TJsonApiRelationships {
	properties: TJsonApiRelation;
	controls: TJsonApiRelation;
	device: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IChannelResponseModel extends TJsonaModel {
	id: string;
	type: { source: string; entity: string };

	identifier: string;
	name: string | null;
	comment: string | null;

	// Relations
	relationshipNames: string[];

	properties: (IPlainRelation | IChannelPropertyResponseModel)[];
	controls: (IPlainRelation | IChannelControlResponseModel)[];
	device: IPlainRelation | IDeviceResponseModel;
}
