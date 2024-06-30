import { ChannelControlDocument } from '@fastybird/metadata-library';
import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';
import { _GettersTree } from 'pinia';

import {
	IChannel,
	IChannelResponseData,
	IControlsAddActionPayload,
	IControl,
	IControlRecordFactoryPayload,
	IPlainRelation,
	IChannelResponseModel,
	IControlResponseModel,
	IControlDatabaseRecord,
	IControlMeta,
} from '../../models/types';

export interface IChannelControlMeta extends IControlMeta {
	parent: 'channel';
}

// STORE
// =====

export interface IChannelControlsState {
	semaphore: IChannelControlsStateSemaphore;
	data: { [key: IChannelControl['id']]: IChannelControl } | undefined;
	meta: { [key: IChannelControl['id']]: IChannelControlMeta };
}

export interface IChannelControlsGetters extends _GettersTree<IChannelControlsState> {
	getting: (state: IChannelControlsState) => (id: IChannelControl['id']) => boolean;
	fetching: (state: IChannelControlsState) => (channelId: IChannel['id'] | null) => boolean;
	findById: (state: IChannelControlsState) => (id: IChannelControl['id']) => IChannelControl | null;
	findByName: (state: IChannelControlsState) => (channel: IChannel, name: IChannelControl['name']) => IChannelControl | null;
	findForChannel: (state: IChannelControlsState) => (channelId: IChannel['id']) => IChannelControl[];
	findMeta: (state: IChannelControlsState) => (id: IChannelControl['id']) => IChannelControlMeta | null;
}

export interface IChannelControlsActions {
	set: (payload: IChannelControlsSetActionPayload) => Promise<IChannelControl>;
	unset: (payload: IChannelControlsUnsetActionPayload) => void;
	get: (payload: IChannelControlsGetActionPayload) => Promise<boolean>;
	fetch: (payload: IChannelControlsFetchActionPayload) => Promise<boolean>;
	add: (payload: IChannelControlsAddActionPayload) => Promise<IChannelControl>;
	save: (payload: IChannelControlsSaveActionPayload) => Promise<IChannelControl>;
	remove: (payload: IChannelControlsRemoveActionPayload) => Promise<boolean>;
	transmitCommand: (payload: IChannelControlsTransmitCommandActionPayload) => Promise<boolean>;
	socketData: (payload: IChannelControlsSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IChannelControlsInsertDataActionPayload) => Promise<boolean>;
	loadRecord: (payload: IChannelControlsLoadRecordActionPayload) => Promise<boolean>;
	loadAllRecords: (payload?: IChannelControlsLoadAllRecordsActionPayload) => Promise<boolean>;
}

// STORE STATE
// ===========

export interface IChannelControlsStateSemaphore {
	fetching: IChannelControlsStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

interface IChannelControlsStateSemaphoreFetching {
	items: string[];
	item: string[];
}

// STORE MODELS
// ============

export interface IChannelControl extends IControl {
	type: IChannelControlMeta;

	// Relations
	channel: IPlainRelation;
}

// STORE DATA FACTORIES
// ====================

export interface IChannelControlRecordFactoryPayload extends IControlRecordFactoryPayload {
	type: IChannelControlMeta;

	// Relations
	channelId?: string;
	channel?: IPlainRelation;
}

// STORE ACTIONS
// =============

export interface IChannelControlsSetActionPayload {
	data: IChannelControlRecordFactoryPayload;
}

export interface IChannelControlsUnsetActionPayload {
	channel?: IChannel;
	id?: IChannelControl['id'];
}

export interface IChannelControlsGetActionPayload {
	channel: IChannel;
	id: IChannelControl['id'];
	refresh?: boolean;
}

export interface IChannelControlsFetchActionPayload {
	channel: IChannel;
	refresh?: boolean;
}

export interface IChannelControlsAddActionPayload extends IControlsAddActionPayload {
	type: IChannelControlMeta;
	channel: IChannel;
}

export interface IChannelControlsSaveActionPayload {
	id: IChannelControl['id'];
}

export interface IChannelControlsRemoveActionPayload {
	id: IChannelControl['id'];
}

export interface IChannelControlsTransmitCommandActionPayload {
	id: IChannelControl['id'];
	value?: string;
}

export interface IChannelControlsSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IChannelControlsInsertDataActionPayload {
	data: ChannelControlDocument | ChannelControlDocument[];
}

export interface IChannelControlsLoadRecordActionPayload {
	id: IChannelControl['id'];
}

export interface IChannelControlsLoadAllRecordsActionPayload {
	channel: IChannel;
}

// API RESPONSES JSONS
// ===================

export interface IChannelControlResponseJson extends TJsonApiBody {
	data: IChannelControlResponseData;
	includes?: IChannelResponseData[];
}

export interface IChannelControlsResponseJson extends TJsonApiBody {
	data: IChannelControlResponseData[];
	includes?: IChannelResponseData[];
}

export interface IChannelControlResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IChannelControlResponseDataAttributes;
	relationships: IChannelControlResponseDataRelationships;
}

interface IChannelControlResponseDataAttributes {
	name: string;
}

interface IChannelControlResponseDataRelationships extends TJsonApiRelationships {
	channel: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IChannelControlResponseModel extends IControlResponseModel {
	type: IChannelControlMeta;

	// Relations
	channel: IPlainRelation | IChannelResponseModel;
}

// DATABASE
// ========

export interface IChannelControlDatabaseRecord extends IControlDatabaseRecord {
	type: IChannelControlMeta;

	// Relations
	channel: IPlainRelation;
}
