import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';
import { _GettersTree } from 'pinia';

import { DataType, PropertyCategory } from '@fastybird/metadata-library';

import {
	IChannel,
	IChannelResponseData,
	IChannelResponseModel,
	IPlainRelation,
	IPropertiesAddActionPayload,
	IPropertiesEditActionPayload,
	IPropertiesSetStateActionPayload,
	IProperty,
	IPropertyRecordFactoryPayload,
	IPropertyResponseModel,
} from '@/models/types';

// STORE
// =====

export interface IChannelPropertiesState {
	semaphore: IChannelPropertiesStateSemaphore;
	firstLoad: string[];
	data: { [key: string]: IChannelProperty };
}

export interface IChannelPropertiesGetters extends _GettersTree<IChannelPropertiesState> {
	firstLoadFinished: (state: IChannelPropertiesState) => (channelId: string) => boolean;
	getting: (state: IChannelPropertiesState) => (propertyId: string) => boolean;
	fetching: (state: IChannelPropertiesState) => (channelId: string | null) => boolean;
	findById: (state: IChannelPropertiesState) => (id: string) => IChannelProperty | null;
	findByIdentifier: (state: IChannelPropertiesState) => (channel: IChannel, identifier: string) => IChannelProperty | null;
	findForChannel: (state: IChannelPropertiesState) => (channelId: string) => IChannelProperty[];
}

export interface IChannelPropertiesActions {
	set: (payload: IChannelPropertiesSetActionPayload) => Promise<IChannelProperty>;
	unset: (payload: IChannelPropertiesUnsetActionPayload) => void;
	get: (payload: IChannelPropertiesGetActionPayload) => Promise<boolean>;
	fetch: (payload: IChannelPropertiesFetchActionPayload) => Promise<boolean>;
	add: (payload: IChannelPropertiesAddActionPayload) => Promise<IChannelProperty>;
	edit: (payload: IChannelPropertiesEditActionPayload) => Promise<IChannelProperty>;
	save: (payload: IChannelPropertiesSaveActionPayload) => Promise<IChannelProperty>;
	setState: (payload: IChannelPropertiesSetStateActionPayload) => Promise<IChannelProperty>;
	remove: (payload: IChannelPropertiesRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IChannelPropertiesSocketDataActionPayload) => Promise<boolean>;
}

// STORE STATE
// ===========

export interface IChannelPropertiesStateSemaphore {
	fetching: IChannelPropertiesStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

interface IChannelPropertiesStateSemaphoreFetching {
	items: string[];
	item: string[];
}

// STORE MODELS
// ============

export interface IChannelProperty extends IProperty {
	// Relations
	channel: IPlainRelation;
	parent: IPlainRelation | null;
	children: IPlainRelation[];
}

// STORE DATA FACTORIES
// ====================

export interface IChannelPropertyRecordFactoryPayload extends IPropertyRecordFactoryPayload {
	// Relations
	channelId: string;
	parentId?: string | null;
}

// STORE ACTIONS
// =============

export interface IChannelPropertiesSetActionPayload {
	data: IChannelPropertyRecordFactoryPayload;
}

export interface IChannelPropertiesUnsetActionPayload {
	channel?: IChannel;
	parent?: IChannelProperty | null;
	id?: string;
}

export interface IChannelPropertiesGetActionPayload {
	channel: IChannel;
	id: string;
}

export interface IChannelPropertiesFetchActionPayload {
	channel: IChannel;
}

export interface IChannelPropertiesAddActionPayload extends IPropertiesAddActionPayload {
	channel: IChannel;
	parent?: IChannelProperty | null;
}

export interface IChannelPropertiesEditActionPayload extends IPropertiesEditActionPayload {
	parent?: IChannelProperty | null;
}

export interface IChannelPropertiesSetStateActionPayload extends IPropertiesSetStateActionPayload {
	parent?: IChannelProperty | null;
}

export interface IChannelPropertiesSaveActionPayload {
	id: string;
}

export interface IChannelPropertiesRemoveActionPayload {
	id: string;
}

export interface IChannelPropertiesSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

// API RESPONSES JSONS
// ===================

export interface IChannelPropertyResponseJson extends TJsonApiBody {
	data: IChannelPropertyResponseData;
	includes?: (IChannelResponseData | IChannelPropertyResponseData)[];
}

export interface IChannelPropertiesResponseJson extends TJsonApiBody {
	data: IChannelPropertyResponseData[];
	includes?: (IChannelResponseData | IChannelPropertyResponseData)[];
}

export interface IChannelPropertyResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IChannelPropertyResponseDataAttributes;
	relationships: IChannelPropertyResponseDataRelationships;
}

interface IChannelPropertyResponseDataAttributes {
	category: PropertyCategory;
	identifier: string;
	name: string | null;
	settable: boolean;
	queryable: boolean;
	data_type: DataType | null;
	unit: string | null;
	format: string[] | (string | null)[][] | (number | null)[] | null;
	invalid: string | number | null;
	scale: number | null;
	step: number | null;

	value: string | number | boolean | null;

	actual_value: string | number | boolean | null;
	expected_value: string | number | boolean | null;
	pending: boolean | Date | null;
}

interface IChannelPropertyResponseDataRelationships extends TJsonApiRelationships {
	channel: TJsonApiRelation;
	parent: TJsonApiRelation;
	children: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IChannelPropertyResponseModel extends IPropertyResponseModel {
	// Relations
	channel: IPlainRelation | IChannelResponseModel;
	parent?: IPlainRelation | IChannelPropertyResponseModel | null;
	children?: (IPlainRelation | IChannelPropertyResponseModel)[];
}
