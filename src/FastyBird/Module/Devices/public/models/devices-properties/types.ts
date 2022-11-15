import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';
import { _GettersTree } from 'pinia';

import { DataType } from '@fastybird/metadata-library';

import {
	IDevice,
	IDeviceResponseData,
	IDeviceResponseModel,
	IPlainRelation,
	IPropertiesAddActionPayload,
	IPropertiesEditActionPayload,
	IProperty,
	IPropertyRecordFactoryPayload,
	IPropertyResponseModel,
} from '@/models/types';

// STORE
// =====

export interface IDevicePropertiesState {
	semaphore: IDevicePropertiesStateSemaphore;
	firstLoad: string[];
	data: { [key: string]: IDeviceProperty };
}

export interface IDevicePropertiesGetters extends _GettersTree<IDevicePropertiesState> {
	firstLoadFinished: (state: IDevicePropertiesState) => (deviceId: string) => boolean;
	getting: (state: IDevicePropertiesState) => (propertyId: string) => boolean;
	fetching: (state: IDevicePropertiesState) => (deviceId: string | null) => boolean;
	findById: (state: IDevicePropertiesState) => (id: string) => IDeviceProperty | null;
	findByIdentifier: (state: IDevicePropertiesState) => (device: IDevice, identifier: string) => IDeviceProperty | null;
	findForDevice: (state: IDevicePropertiesState) => (deviceId: string) => IDeviceProperty[];
}

export interface IDevicePropertiesActions {
	set: (payload: IDevicePropertiesSetActionPayload) => Promise<IDeviceProperty>;
	unset: (payload: IDevicePropertiesUnsetActionPayload) => void;
	get: (payload: IDevicePropertiesGetActionPayload) => Promise<boolean>;
	fetch: (payload: IDevicePropertiesFetchActionPayload) => Promise<boolean>;
	add: (payload: IDevicePropertiesAddActionPayload) => Promise<IDeviceProperty>;
	edit: (payload: IDevicePropertiesEditActionPayload) => Promise<IDeviceProperty>;
	save: (payload: IDevicePropertiesSaveActionPayload) => Promise<IDeviceProperty>;
	remove: (payload: IDevicePropertiesRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IDevicePropertiesSocketDataActionPayload) => Promise<boolean>;
}

// STORE STATE
// ===========

export interface IDevicePropertiesStateSemaphore {
	fetching: IDevicePropertiesStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

interface IDevicePropertiesStateSemaphoreFetching {
	items: string[];
	item: string[];
}

// STORE MODELS
// ============

export interface IDeviceProperty extends IProperty {
	// Relations
	device: IPlainRelation;
	parent: IPlainRelation | null;
	children: IPlainRelation[];
}

// STORE DATA FACTORIES
// ====================

export interface IDevicePropertyRecordFactoryPayload extends IPropertyRecordFactoryPayload {
	// Relations
	deviceId: string;
	parentId?: string | null;
}

// STORE ACTIONS
// =============

export interface IDevicePropertiesSetActionPayload {
	data: IDevicePropertyRecordFactoryPayload;
}

export interface IDevicePropertiesUnsetActionPayload {
	device?: IDevice;
	parent?: IDeviceProperty | null;
	id?: string;
}

export interface IDevicePropertiesGetActionPayload {
	device: IDevice;
	id: string;
}

export interface IDevicePropertiesFetchActionPayload {
	device: IDevice;
}

export interface IDevicePropertiesAddActionPayload extends IPropertiesAddActionPayload {
	device: IDevice;
	parent?: IDeviceProperty | null;
}

export interface IDevicePropertiesEditActionPayload extends IPropertiesEditActionPayload {
	parent?: IDeviceProperty | null;
}

export interface IDevicePropertiesSaveActionPayload {
	id: string;
}

export interface IDevicePropertiesRemoveActionPayload {
	id: string;
}

export interface IDevicePropertiesSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

// API RESPONSES JSONS
// ===================

export interface IDevicePropertyResponseJson extends TJsonApiBody {
	data: IDevicePropertyResponseData;
	includes?: (IDeviceResponseData | IDevicePropertyResponseData)[];
}

export interface IDevicePropertiesResponseJson extends TJsonApiBody {
	data: IDevicePropertyResponseData[];
	includes?: (IDeviceResponseData | IDevicePropertyResponseData)[];
}

export interface IDevicePropertyResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IDevicePropertyResponseDataAttributes;
	relationships: IDevicePropertyResponseDataRelationships;
}

interface IDevicePropertyResponseDataAttributes {
	identifier: string;
	name: string | null;
	settable: boolean;
	queryable: boolean;
	data_type: DataType;
	unit: string | null;
	format: string[] | (string | null)[][] | (number | null)[] | null;
	invalid: string | number | null;
	number_of_decimals: number | null;

	value: string | number | boolean | null;

	actual_value: string | number | boolean | null;
	expected_value: string | number | boolean | null;
	pending: boolean;
}

interface IDevicePropertyResponseDataRelationships extends TJsonApiRelationships {
	device: TJsonApiRelation;
	parent: TJsonApiRelation;
	children: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IDevicePropertyResponseModel extends IPropertyResponseModel {
	device: IPlainRelation | IDeviceResponseModel;
	parent?: IPlainRelation | IDevicePropertyResponseModel | null;
	children?: (IPlainRelation | IDevicePropertyResponseModel)[];
}
