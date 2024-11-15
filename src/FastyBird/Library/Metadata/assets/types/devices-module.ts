import { ButtonPayload, CoverPayload, DataType, SwitchPayload } from '@/types/types';

export enum ConnectionState {
	CONNECTED = 'connected',
	DISCONNECTED = 'disconnected',
	INIT = 'init',
	READY = 'ready',
	RUNNING = 'running',
	SLEEPING = 'sleeping',
	STOPPED = 'stopped',
	LOST = 'lost',
	ALERT = 'alert',
	UNKNOWN = 'unknown',
}

export enum ConnectorCategory {
	GENERIC = 'generic',
}

export enum DeviceCategory {
	GENERIC = 'generic',
}

export enum ChannelCategory {
	GENERIC = 'generic',
}

export enum PropertyCategory {
	GENERIC = 'generic',
}

export interface ConnectorDocument {
	id: string;
	type: string;
	source: string;
	category: ConnectorCategory;
	identifier: string;
	name: string;
	comment: string | null;
	enabled: boolean;
	properties: string[];
	controls: string[];
	devices: string[];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface ConnectorPropertyDocument {
	id: string;
	type: 'dynamic' | 'variable';
	source: string;
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
	value_transformer: string | null;
	default?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	actual_value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	expected_value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	pending?: boolean | Date;
	is_valid?: boolean;
	connector: string;
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface ConnectorControlDocument {
	id: string;
	type: string;
	source: string;
	name: string;
	connector: string;
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface DeviceDocument {
	id: string;
	type: string;
	source: string;
	category: DeviceCategory;
	identifier: string;
	name: string | null;
	comment: string | null;
	connector: string;
	parents: string[];
	children: string[];
	properties: string[];
	controls: string[];
	channels: string[];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface DevicePropertyDocument {
	id: string;
	type: 'dynamic' | 'variable' | 'mapped';
	source: string;
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
	value_transformer: string | null;
	default?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	actual_value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	expected_value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	pending?: boolean | Date;
	is_valid?: boolean;
	device: string;
	parent?: string;
	children: string[];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface DeviceControlDocument {
	id: string;
	type: string;
	source: string;
	name: string;
	device: string;
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface ChannelDocument {
	id: string;
	type: string;
	source: string;
	category: ChannelCategory;
	identifier: string;
	name: string | null;
	comment: string | null;
	device: string;
	properties: string[];
	controls: string[];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface ChannelPropertyDocument {
	id: string;
	type: 'dynamic' | 'variable' | 'mapped';
	source: string;
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
	value_transformer: string | null;
	default?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	actual_value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	expected_value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	pending?: boolean | Date;
	is_valid?: boolean;
	channel: string;
	parent?: string;
	children: string[];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface ChannelControlDocument {
	id: string;
	type: string;
	source: string;
	name: string;
	channel: string;
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}
