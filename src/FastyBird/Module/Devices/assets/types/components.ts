import { Reactive } from 'vue';

import { ConnectionState } from '@fastybird/metadata-library';

import { IChannel, IChannelProperty, IConnector, IConnectorProperty, IDevice, IDeviceProperty } from '../models/types';

import {
	FormResultTypes,
	IBridge,
	IDebugLog,
	IService,
	IConnectorData,
	IDeviceData,
	IConnectorPlugin,
	IChannelData,
	SimpleStateFilter,
	PropertyType,
} from './index';

export interface IDevicesFilter {
	search: string;
	state: SimpleStateFilter;
	states: ConnectionState[];
	plugins: IConnectorPlugin['type'][];
	connectors: IConnector['id'][];
}

export type DevicesFilter = Reactive<IDevicesFilter>;

export interface IConnectorDetailProps {
	loading: boolean;
	connectorData: IConnectorData;
	alerts: IDebugLog[];
	bridges: IBridge[];
	service: IService | null;
}

export interface IConnectorDevicesProps {
	loading: boolean;
	connectorData: IConnectorData;
}

export interface IConnectorDevicesEmits {
	(e: 'detail', id: IDevice['id'], event: Event): void;
	(e: 'edit', id: IDevice['id'], event: Event): void;
	(e: 'remove', id: IDevice['id'], event: Event): void;
	(e: 'add', event: Event): void;
}

export const connectorDevicesEmits = {
	detail: (id: IDevice['id'], event: Event): boolean => typeof id === 'string' && event instanceof Event,
	edit: (id: IDevice['id'], event: Event): boolean => typeof id === 'string' && event instanceof Event,
	remove: (id: IDevice['id'], event: Event): boolean => typeof id === 'string' && event instanceof Event,
	add: (event: Event): boolean => event instanceof Event,
};

export interface IConnectorDeviceProps {
	loading: boolean;
	connectorData: IConnectorData;
	deviceData: IDeviceData;
}

export interface IConnectorDeviceEmits {
	(e: 'detail', event: Event): void;
	(e: 'edit', event: Event): void;
	(e: 'remove', event: Event): void;
}

export const connectorDeviceEmits = {
	detail: (event: Event): boolean => event instanceof Event,
	edit: (event: Event): boolean => event instanceof Event,
	remove: (event: Event): boolean => event instanceof Event,
};

export interface IEditConnectorProps {
	connectorData: IConnectorData;
	loading: boolean;
	devicesLoading: boolean;
	remoteFormSubmit?: boolean;
	remoteFormResult?: FormResultTypes;
	remoteFormReset?: boolean;
}

export interface IEditConnectorEmits {
	(e: 'update:remoteFormSubmit', remoteFormSubmit: boolean): void;
	(e: 'update:remoteFormResult', remoteFormResult: FormResultTypes): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
	(e: 'addProperty', type: PropertyType, event: Event): void;
	(e: 'editProperty', id: IConnectorProperty['id'], event: Event): void;
	(e: 'removeProperty', id: IConnectorProperty['id'], event: Event): void;
}

export const editConnectorEmits = {
	'update:remoteFormSubmit': (remoteFormSubmit: boolean): boolean => typeof remoteFormSubmit === 'boolean',
	'update:remoteFormResult': (remoteFormResult: FormResultTypes): boolean => typeof remoteFormResult === 'string',
	'update:remoteFormReset': (remoteFormReset: boolean): boolean => typeof remoteFormReset === 'boolean',
	addProperty: (type: PropertyType, event: Event): boolean => typeof type === 'string' && event instanceof Event,
	editProperty: (id: IConnectorProperty['id'], event: Event): boolean => typeof id === 'string' && event instanceof Event,
	removeProperty: (id: IConnectorProperty['id'], event: Event): boolean => typeof id === 'string' && event instanceof Event,
};

export interface IDeviceDetailProps {
	loading: boolean;
	deviceData: IDeviceData;
	alerts: IDebugLog[];
	bridges: IBridge[];
}

export interface IDeviceChannelsProps {
	loading: boolean;
	deviceData: IDeviceData;
}

export interface IDeviceChannelsEmits {
	(e: 'detail', id: IChannel['id'], event: Event): void;
	(e: 'edit', id: IChannel['id'], event: Event): void;
	(e: 'remove', id: IChannel['id'], event: Event): void;
	(e: 'add', event: Event): void;
}

export const deviceChannelsEmits = {
	detail: (id: IChannel['id'], event: Event): boolean => typeof id === 'string' && event instanceof Event,
	edit: (id: IChannel['id'], event: Event): boolean => typeof id === 'string' && event instanceof Event,
	remove: (id: IChannel['id'], event: Event): boolean => typeof id === 'string' && event instanceof Event,
	add: (event: Event): boolean => event instanceof Event,
};

export interface IDeviceChannelProps {
	loading: boolean;
	deviceData: IDeviceData;
	channelData: IChannelData;
}

export interface IDeviceChannelEmits {
	(e: 'detail', event: Event): void;
	(e: 'edit', event: Event): void;
	(e: 'remove', event: Event): void;
}

export const deviceChannelEmits = {
	detail: (event: Event): boolean => event instanceof Event,
	edit: (event: Event): boolean => event instanceof Event,
	remove: (event: Event): boolean => event instanceof Event,
};

export interface IEditDeviceProps {
	deviceData: IDeviceData;
	loading: boolean;
	channelsLoading: boolean;
	remoteFormSubmit?: boolean;
	remoteFormResult?: FormResultTypes;
	remoteFormReset?: boolean;
}

export interface IEditDeviceEmits {
	(e: 'update:remoteFormSubmit', remoteFormSubmit: boolean): void;
	(e: 'update:remoteFormResult', remoteFormResult: FormResultTypes): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
	(e: 'addProperty', type: PropertyType, event: Event): void;
	(e: 'editProperty', id: IDeviceProperty['id'], event: Event): void;
	(e: 'removeProperty', id: IDeviceProperty['id'], event: Event): void;
}

export const editDeviceEmits = {
	'update:remoteFormSubmit': (remoteFormSubmit: boolean): boolean => typeof remoteFormSubmit === 'boolean',
	'update:remoteFormResult': (remoteFormResult: FormResultTypes): boolean => typeof remoteFormResult === 'string',
	'update:remoteFormReset': (remoteFormReset: boolean): boolean => typeof remoteFormReset === 'boolean',
	addProperty: (type: PropertyType, event: Event): boolean => typeof type === 'string' && event instanceof Event,
	editProperty: (id: IDeviceProperty['id'], event: Event): boolean => typeof id === 'string' && event instanceof Event,
	removeProperty: (id: IDeviceProperty['id'], event: Event): boolean => typeof id === 'string' && event instanceof Event,
};

export interface IChannelDetailProps {
	loading: boolean;
	channelData: IChannelData;
	alerts: IDebugLog[];
}

export interface IChannelDetailEmits {
	(e: 'add', event: Event): void;
}

export const channelDetailEmits = {
	add: (event: Event): boolean => event instanceof Event,
};

export interface IEditChannelProps {
	channelData: IChannelData;
	loading: boolean;
	remoteFormSubmit?: boolean;
	remoteFormResult?: FormResultTypes;
	remoteFormReset?: boolean;
}

export interface IEditChannelEmits {
	(e: 'update:remoteFormSubmit', remoteFormSubmit: boolean): void;
	(e: 'update:remoteFormResult', remoteFormResult: FormResultTypes): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
	(e: 'addProperty', type: PropertyType, event: Event): void;
	(e: 'editProperty', id: IChannelProperty['id'], event: Event): void;
	(e: 'removeProperty', id: IChannelProperty['id'], event: Event): void;
}

export const editChannelEmits = {
	'update:remoteFormSubmit': (remoteFormSubmit: boolean): boolean => typeof remoteFormSubmit === 'boolean',
	'update:remoteFormResult': (remoteFormResult: FormResultTypes): boolean => typeof remoteFormResult === 'string',
	'update:remoteFormReset': (remoteFormReset: boolean): boolean => typeof remoteFormReset === 'boolean',
	addProperty: (type: PropertyType, event: Event): boolean => typeof type === 'string' && event instanceof Event,
	editProperty: (id: IChannelProperty['id'], event: Event): boolean => typeof id === 'string' && event instanceof Event,
	removeProperty: (id: IChannelProperty['id'], event: Event): boolean => typeof id === 'string' && event instanceof Event,
};
