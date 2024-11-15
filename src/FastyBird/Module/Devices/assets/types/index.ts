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
export * from './connector';
export * from './components';
export * from './forms';

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
	device: IDevice | null;
	channel: IChannel;
	properties: IChannelProperty[];
	controls: IChannelControl[];
}

export interface IDeviceData {
	connector: IConnector | null;
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
	deviceCreate: string;
	deviceDetail: string;
	deviceSettings: string;

	channelCreate: string;
	channelDetail: string;
	channelSettings: string;

	plugins: string;
	pluginInstall: string;
	pluginDetail: string;

	connectorCreate: string;
	connectorDetail: string;
	connectorSettings: string;

	connectorDetailDeviceCreate: string;
	connectorDetailDeviceDetail: string;
	connectorDetailDeviceSettings: string;
	connectorDetailDeviceDetailChannelCreate: string;
	connectorDetailDeviceDetailChannelDetail: string;
	connectorDetailDeviceDetailChannelSettings: string;
}

export enum PropertyType {
	DYNAMIC = 'dynamic',
	VARIABLE = 'variable',
	MAPPED = 'mapped',
}

export interface IBridge {
	any: any;
}

export interface IDebugLog {
	type: 'debug' | 'info' | 'warning' | 'error';
}

export interface IService {
	running: boolean;
}

export enum ConnectorPropertyIdentifier {
	STATE = 'state',
	SERVER = 'server',
	PORT = 'port',
	SECURED_PORT = 'secured_port',
	BAUD_RATE = 'baud_rate',
	INTERFACE = 'interface',
	ADDRESS = 'address',
}

export enum DevicePropertyIdentifier {
	STATE = 'state',
	BATTERY = 'battery',
	WIFI = 'wifi',
	SIGNAL = 'signal',
	RSSI = 'rssi',
	SSID = 'ssid',
	VCC = 'vcc',
	CPU_LOAD = 'cpu_load',
	UPTIME = 'uptime',
	ADDRESS = 'address',
	IP_ADDRESS = 'ip_address',
	DOMAIN = 'domain',
	STATUS_LED = 'status_led',
	FREE_HEAP = 'free_heap',
	HARDWARE_MANUFACTURER = 'hardware_manufacturer',
	HARDWARE_MODEL = 'hardware_model',
	HARDWARE_VERSION = 'hardware_version',
	HARDWARE_MAC_ADDRESS = 'hardware_mac_address',
	FIRMWARE_MANUFACTURER = 'firmware_manufacturer',
	FIRMWARE_NAME = 'firmware_name',
	FIRMWARE_VERSION = 'firmware_version',
	SERIAL_NUMBER = 'serial_number',
	STATE_READING_DELAY = 'state_reading_delay',
	STATE_PROCESSING_DELAY = 'state_processing_delay',
}

export enum ChannelPropertyIdentifier {
	ADDRESS = 'address',
}

export type StateColor = 'info' | 'warning' | 'success' | 'primary' | 'danger' | undefined;

export enum SimpleStateFilter {
	ALL = 'all',
	ONLINE = 'online',
	OFFLINE = 'offline',
}
