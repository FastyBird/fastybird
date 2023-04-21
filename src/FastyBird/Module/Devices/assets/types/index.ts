import { Plugin } from 'vue';
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
} from '@/models/types';

export * from '@/components/types';
export * from '@/composables/types';
export * from '@/models/types';
export * from '@/views/types';

export type InstallFunction = Plugin & { installed?: boolean };

export interface IDevicesModuleOptions {
	router?: Router;
	meta: IDevicesModuleMeta;
	configuration: IDevicesModuleConfiguration;
	wsClient?: Client;
}

export interface IDevicesModuleMeta {
	[key: string]: any;
}

export interface IDevicesModuleConfiguration {
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
