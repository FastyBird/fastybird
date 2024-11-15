import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';
import { _GettersTree } from 'pinia';

import { ButtonPayload, CoverPayload, DataType, DevicePropertyDocument, PropertyCategory, SwitchPayload } from '@fastybird/metadata-library';

import {
	IDevice,
	IDeviceResponseData,
	IDeviceResponseModel,
	IPlainRelation,
	IPropertiesAddActionPayload,
	IPropertiesEditActionPayload,
	IPropertiesSetStateActionPayload,
	IProperty,
	IPropertyDatabaseRecord,
	IPropertyMeta,
	IPropertyRecordFactoryPayload,
	IPropertyResponseModel,
} from '../../models/types';

export interface IDevicePropertyMeta extends IPropertyMeta {
	parent: 'device';
	type: 'dynamic' | 'variable' | 'mapped';
}

// STORE
// =====

export interface IDevicePropertiesState {
	semaphore: IDevicePropertiesStateSemaphore;
	firstLoad: IDevice['id'][];
	data: { [key: IDeviceProperty['id']]: IDeviceProperty } | undefined;
	meta: { [key: IDeviceProperty['id']]: IDevicePropertyMeta };
}

export interface IDevicePropertiesGetters extends _GettersTree<IDevicePropertiesState> {
	firstLoadFinished: (state: IDevicePropertiesState) => (deviceId: IDevice['id']) => boolean;
	getting: (state: IDevicePropertiesState) => (id: IDeviceProperty['id']) => boolean;
	fetching: (state: IDevicePropertiesState) => (deviceId: IDevice['id'] | null) => boolean;
	findById: (state: IDevicePropertiesState) => (id: IDeviceProperty['id']) => IDeviceProperty | null;
	findByIdentifier: (state: IDevicePropertiesState) => (device: IDevice, identifier: IDeviceProperty['identifier']) => IDeviceProperty | null;
	findForDevice: (state: IDevicePropertiesState) => (deviceId: IDevice['id']) => IDeviceProperty[];
	findMeta: (state: IDevicePropertiesState) => (id: IDeviceProperty['id']) => IDevicePropertyMeta | null;
}

export interface IDevicePropertiesActions {
	set: (payload: IDevicePropertiesSetActionPayload) => Promise<IDeviceProperty>;
	unset: (payload: IDevicePropertiesUnsetActionPayload) => Promise<void>;
	get: (payload: IDevicePropertiesGetActionPayload) => Promise<boolean>;
	fetch: (payload: IDevicePropertiesFetchActionPayload) => Promise<boolean>;
	add: (payload: IDevicePropertiesAddActionPayload) => Promise<IDeviceProperty>;
	edit: (payload: IDevicePropertiesEditActionPayload) => Promise<IDeviceProperty>;
	setState: (payload: IDevicePropertiesSetStateActionPayload) => Promise<IDeviceProperty>;
	save: (payload: IDevicePropertiesSaveActionPayload) => Promise<IDeviceProperty>;
	remove: (payload: IDevicePropertiesRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IDevicePropertiesSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IDevicePropertiesInsertDataActionPayload) => Promise<boolean>;
	loadRecord: (payload: IDevicePropertiesLoadRecordActionPayload) => Promise<boolean>;
	loadAllRecords: (payload?: IDevicePropertiesLoadAllRecordsActionPayload) => Promise<boolean>;
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
	type: IDevicePropertyMeta;

	// Relations
	device: IPlainRelation;
	parent: IPlainRelation | null;
	children: IPlainRelation[];
}

// STORE DATA FACTORIES
// ====================

export interface IDevicePropertyRecordFactoryPayload extends IPropertyRecordFactoryPayload {
	type: IDevicePropertyMeta;

	// Relations
	deviceId?: string;
	device?: IPlainRelation;
	parentId?: string | null;
	parent?: IPlainRelation | null;
}

// STORE ACTIONS
// =============

export interface IDevicePropertiesSetActionPayload {
	data: IDevicePropertyRecordFactoryPayload;
}

export interface IDevicePropertiesUnsetActionPayload {
	device?: IDevice;
	parent?: IDeviceProperty | null;
	id?: IDeviceProperty['id'];
}

export interface IDevicePropertiesGetActionPayload {
	device: IDevice;
	id: IDeviceProperty['id'];
	refresh?: boolean;
}

export interface IDevicePropertiesFetchActionPayload {
	device: IDevice;
	refresh?: boolean;
}

export interface IDevicePropertiesAddActionPayload extends IPropertiesAddActionPayload {
	type: IDevicePropertyMeta;
	device: IDevice;
	parent?: IDeviceProperty | null;
}

export interface IDevicePropertiesEditActionPayload extends IPropertiesEditActionPayload {
	parent?: IDeviceProperty | null;
}

export interface IDevicePropertiesSetStateActionPayload extends IPropertiesSetStateActionPayload {
	parent?: IDeviceProperty | null;
}

export interface IDevicePropertiesSaveActionPayload {
	id: IDeviceProperty['id'];
}

export interface IDevicePropertiesRemoveActionPayload {
	id: IDeviceProperty['id'];
}

export interface IDevicePropertiesSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IDevicePropertiesInsertDataActionPayload {
	data: DevicePropertyDocument | DevicePropertyDocument[];
}

export interface IDevicePropertiesLoadRecordActionPayload {
	id: IDeviceProperty['id'];
}

export interface IDevicePropertiesLoadAllRecordsActionPayload {
	device: IDevice;
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
	category: PropertyCategory;
	identifier: string;
	name: string | null;
	settable: boolean;
	queryable: boolean;
	data_type: DataType;
	unit: string | null;
	format: string[] | (string | null)[][] | (number | null)[] | string | null;
	invalid: string | number | boolean | null;
	scale: number | null;
	step: number | null;

	value: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;

	actual_value: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	expected_value: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	pending: boolean | Date;
	isValid: boolean;
}

interface IDevicePropertyResponseDataRelationships extends TJsonApiRelationships {
	device: TJsonApiRelation;
	parent: TJsonApiRelation;
	children: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IDevicePropertyResponseModel extends IPropertyResponseModel {
	type: IDevicePropertyMeta;

	// Relations
	device: IPlainRelation | IDeviceResponseModel;
	parent?: IPlainRelation | IDevicePropertyResponseModel | null;
	children?: (IPlainRelation | IDevicePropertyResponseModel)[];
}

// DATABASE
// ========

export interface IDevicePropertyDatabaseRecord extends IPropertyDatabaseRecord {
	type: IDevicePropertyMeta;

	// Relations
	device: IPlainRelation;
	parent: IPlainRelation | null;
	children: IPlainRelation[];
}
