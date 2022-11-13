import { Plugin } from 'vue';
import { Router } from 'vue-router';

import {
	IChannel,
	IChannelControl,
	IChannelProperty,
	IConnector,
	IConnectorControl,
	IConnectorProperty,
	IDevice,
	IDeviceControl,
	IDeviceAttribute,
	IDeviceProperty,
} from '@/models/types';

export * from '@/composables/types';
export * from '@/models/types';

export type InstallFunction = Plugin & { installed?: boolean };

export interface IDevicesModuleOptions {
	router?: Router;
	meta: IDeviceModuleMeta;
	configuration: IDeviceModuleConfiguration;
}

export interface IDeviceModuleMeta {
	[key: string]: any;
}

export interface IDeviceModuleConfiguration {
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
	attributes: IDeviceAttribute[];
	channels: IChannelData[];
}

export interface IConnectorData {
	connector: IConnector;
	properties: IConnectorProperty[];
	controls: IConnectorControl[];
	devices: IDeviceData[];
}
