import { Pinia } from 'pinia';
import { Plugin } from 'vue';
import { I18n } from 'vue-i18n';
import { Router } from 'vue-router';
import { Client } from '@fastybird/vue-wamp-v1';

import {
	IChannel,
	IChannelControl,
	IChannelProperty,
	IConnector,
	IConnectorControl,
	IConnectorProperty,
	IDevice,
	IDeviceControl,
	IDeviceProperty,
} from '../models/types';

export * from '../components/types';
export * from '../composables/types';
export * from '../models/types';
export * from '../views/types';

export type InstallFunction = Plugin & { installed?: boolean };

export interface IDevicesModuleOptions {
	router?: Router;
	meta: IDevicesModuleMeta;
	configuration: IDevicesModuleConfiguration;
	store: Pinia;
	wsClient?: Client;
	i18n?: I18n;
}

export interface IDevicesModuleMeta {
	author: string;
	website: string;
	version: string;
	[key: string]: any;
}

export interface IDevicesModuleConfiguration {
	injectionKeys: {
		eventBusInjectionKey?: symbol | string;
	};
	[key: string]: any;
}

export interface IChannelData {
	channel: IChannel;
	properties: IChannelProperty[];
	controls: IChannelControl[];
}

export interface IDeviceData {
	device: IDevice;
	properties: IDeviceProperty[];
	controls: IDeviceControl[];
	channels: IChannelData[];
}

export interface IConnectorData {
	connector: IConnector;
	properties: IConnectorProperty[];
	controls: IConnectorControl[];
	devices: IDeviceData[];
}

export interface IRoutes {
	root: string;

	devices: string;
	deviceConnect: string;
	deviceDetail: string;
	deviceSettings: string;
	deviceSettingsAddChannel: string;
	deviceSettingsEditChannel: string;

	connectors: string;
	connectorRegister: string;
	connectorDetail: string;
	connectorSettings: string;
	connectorSettingsAddDevice: string;
	connectorSettingsEditDevice: string;
	connectorSettingsEditDeviceAddChannel: string;
	connectorSettingsEditDeviceEditChannel: string;
}

export enum FormResultTypes {
	NONE = 'none',
	WORKING = 'working',
	ERROR = 'error',
	OK = 'ok',
}

export type FormResultType = FormResultTypes.NONE | FormResultTypes.WORKING | FormResultTypes.ERROR | FormResultTypes.OK;
