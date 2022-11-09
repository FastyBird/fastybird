import { TJsonaModel, TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';
import { _GettersTree } from 'pinia';

import { IDevice, IDeviceResponseData, IPlainRelation, IDeviceResponseModel } from '@/lib/models/types';

// STORE
// =====

export interface IDeviceAttributesState {
	semaphore: IDeviceAttributesStateSemaphore;
	firstLoad: string[];
	data: { [key: string]: IDeviceAttribute };
}

export interface IDeviceAttributesGetters extends _GettersTree<IDeviceAttributesState> {
	firstLoadFinished: (state: IDeviceAttributesState) => (deviceId: string) => boolean;
	getting: (state: IDeviceAttributesState) => (attributeId: string) => boolean;
	fetching: (state: IDeviceAttributesState) => (deviceId: string | null) => boolean;
	findById: (state: IDeviceAttributesState) => (id: string) => IDeviceAttribute | null;
	findByIdentifier: (state: IDeviceAttributesState) => (device: IDevice, identifier: string) => IDeviceAttribute | null;
	findForDevice: (state: IDeviceAttributesState) => (deviceId: string) => IDeviceAttribute[];
}

export interface IDeviceAttributesActions {
	set: (payload: IDeviceAttributesSetActionPayload) => Promise<IDeviceAttribute>;
	unset: (payload: IDeviceAttributesUnsetActionPayload) => void;
	get: (payload: IDeviceAttributesGetActionPayload) => Promise<boolean>;
	fetch: (payload: IDeviceAttributesFetchActionPayload) => Promise<boolean>;
	add: (payload: IDeviceAttributesAddActionPayload) => Promise<IDeviceAttribute>;
	save: (payload: IDeviceAttributesSaveActionPayload) => Promise<IDeviceAttribute>;
	remove: (payload: IDeviceAttributesRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IDeviceAttributesSocketDataActionPayload) => Promise<boolean>;
}

// STORE STATE
// ===========

export interface IDeviceAttributesStateSemaphore {
	fetching: IDeviceAttributesStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

interface IDeviceAttributesStateSemaphoreFetching {
	items: string[];
	item: string[];
}

// STORE MODELS
// ============

export interface IDeviceAttribute {
	id: string;
	type: { source: string; parent: string; entity: string };

	draft: boolean;

	identifier: string;
	name: string | null;
	content: string | null;

	// Relations
	relationshipNames: string[];

	device: IPlainRelation;
}

// STORE DATA FACTORIES
// ====================

export interface IDeviceAttributeRecordFactoryPayload {
	id?: string;
	type: { source: string; parent?: string; entity?: string };

	draft?: boolean;

	identifier: string;
	name?: string | null;
	content?: string | null;

	// Relations
	relationshipNames?: string[];

	deviceId: string;
}

// STORE ACTIONS
// =============

export interface IDeviceAttributesSetActionPayload {
	data: IDeviceAttributeRecordFactoryPayload;
}

export interface IDeviceAttributesUnsetActionPayload {
	device?: IDevice;
	id?: string;
}

export interface IDeviceAttributesGetActionPayload {
	device: IDevice;
	id: string;
}

export interface IDeviceAttributesFetchActionPayload {
	device: IDevice;
}

export interface IDeviceAttributesAddActionPayload {
	device: IDevice;
	id?: string;
	type: { source: string; parent?: string; entity?: string };

	draft?: boolean;

	data: {
		identifier: string;
		name?: string | null;
		content?: string | null;
	};
}

export interface IDeviceAttributesSaveActionPayload {
	id: string;
}

export interface IDeviceAttributesRemoveActionPayload {
	id: string;
}

export interface IDeviceAttributesSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

// API RESPONSES JSONS
// ===================

export interface IDeviceAttributeResponseJson extends TJsonApiBody {
	data: IDeviceAttributeResponseData;
	includes?: IDeviceResponseData[];
}

export interface IDeviceAttributesResponseJson extends TJsonApiBody {
	data: IDeviceAttributeResponseData[];
	includes?: IDeviceResponseData[];
}

export interface IDeviceAttributeResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IDeviceAttributeResponseDataAttributes;
	relationships: IDeviceAttributeResponseDataRelationships;
}

interface IDeviceAttributeResponseDataAttributes {
	identifier: string;
	name: string | null;
	content: string | null;
}

interface IDeviceAttributeResponseDataRelationships extends TJsonApiRelationships {
	device: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IDeviceAttributeResponseModel extends TJsonaModel {
	id: string;
	type: { source: string; parent: string; entity: string };

	identifier: string;
	name: string | null;
	content: string | null;

	// Relations
	relationshipNames: string[];

	device: IPlainRelation | IDeviceResponseModel;
}
