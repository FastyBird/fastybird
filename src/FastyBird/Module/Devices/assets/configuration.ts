import { StateTree, Store } from 'pinia';
import { InjectionKey } from 'vue';

import { ConnectorSource } from '@fastybird/metadata-library';

import {
	IChannelControlsActions,
	IChannelControlsGetters,
	IChannelControlsState,
	IChannelPropertiesActions,
	IChannelPropertiesGetters,
	IChannelPropertiesState,
	IChannelsActions,
	IChannelsGetters,
	IChannelsState,
	IConnectorControlsActions,
	IConnectorControlsGetters,
	IConnectorControlsState,
	IConnectorPlugin,
	IConnectorPropertiesActions,
	IConnectorPropertiesGetters,
	IConnectorPropertiesState,
	IConnectorsActions,
	IConnectorsGetters,
	IConnectorsState,
	IDeviceControlsActions,
	IDeviceControlsGetters,
	IDeviceControlsState,
	IDevicePropertiesActions,
	IDevicePropertiesGetters,
	IDevicePropertiesState,
	IDevicesActions,
	IDevicesGetters,
	IDevicesModuleConfiguration,
	IDevicesModuleMeta,
	IDevicesState,
} from './types';

export type StoreInjectionKey<Id extends string = string, S extends StateTree = object, G = object, A = object> = InjectionKey<Store<Id, S, G, A>>;

export const metaKey: InjectionKey<IDevicesModuleMeta> = Symbol('devices-module_meta');
export const configurationKey: InjectionKey<IDevicesModuleConfiguration> = Symbol('devices-module_configuration');

export const channelsStoreKey: StoreInjectionKey<string, IChannelsState, IChannelsGetters, IChannelsActions> =
	Symbol('devices-module_store_channels');
export const channelControlsStoreKey: StoreInjectionKey<string, IChannelControlsState, IChannelControlsGetters, IChannelControlsActions> = Symbol(
	'devices-module_store_channel_controls'
);
export const channelPropertiesStoreKey: StoreInjectionKey<string, IChannelPropertiesState, IChannelPropertiesGetters, IChannelPropertiesActions> =
	Symbol('devices-module_store_channel_properties');
export const connectorsStoreKey: StoreInjectionKey<string, IConnectorsState, IConnectorsGetters, IConnectorsActions> =
	Symbol('devices-module_store_connectors');
export const connectorControlsStoreKey: StoreInjectionKey<string, IConnectorControlsState, IConnectorControlsGetters, IConnectorControlsActions> =
	Symbol('devices-module_store_connector_controls');
export const connectorPropertiesStoreKey: StoreInjectionKey<
	string,
	IConnectorPropertiesState,
	IConnectorPropertiesGetters,
	IConnectorPropertiesActions
> = Symbol('devices-module_store_connector_properties');
export const devicesStoreKey: StoreInjectionKey<string, IDevicesState, IDevicesGetters, IDevicesActions> = Symbol('devices-module_store_devices');
export const deviceControlsStoreKey: StoreInjectionKey<string, IDeviceControlsState, IDeviceControlsGetters, IDeviceControlsActions> = Symbol(
	'devices-module_store_device_controls'
);
export const devicePropertiesStoreKey: StoreInjectionKey<string, IDevicePropertiesState, IDevicePropertiesGetters, IDevicePropertiesActions> = Symbol(
	'devices-module_store_device_properties'
);

export const connectorPlugins: IConnectorPlugin[] = [
	{
		type: 'ns-panel-connector',
		source: ConnectorSource.NS_PANEL,
		name: 'NS Panel',
		description: 'FastyBird IoT connector for Sonoff NS Panel Pro devices',
		links: {
			documentation: 'http://www.fastybird.com',
			devDocumentation: 'http://www.fastybird.com',
			bugsTracking: 'http://www.fastybird.com',
		},
		components: {
			connectorDetail: undefined,
			addConnector: undefined,
			editConnector: undefined,
		},
		core: true,
	},
	{
		type: 'shelly-connector',
		source: ConnectorSource.SHELLY,
		name: 'Shelly',
		description: 'FastyBird IoT connector for Shelly devices',
		links: {
			documentation: 'http://www.fastybird.com',
			devDocumentation: 'http://www.fastybird.com',
			bugsTracking: 'http://www.fastybird.com',
		},
		components: {
			connectorDetail: undefined,
			addConnector: undefined,
			editConnector: undefined,
		},
		core: true,
	},
	{
		type: 'sonoff-connector',
		source: ConnectorSource.SONOFF,
		name: 'Sonoff',
		description: 'FastyBird IoT connector for Sonoff devices',
		links: {
			documentation: 'http://www.fastybird.com',
			devDocumentation: 'http://www.fastybird.com',
			bugsTracking: 'http://www.fastybird.com',
		},
		components: {
			connectorDetail: undefined,
			addConnector: undefined,
			editConnector: undefined,
		},
		core: true,
	},
	{
		type: 'tuya-connector',
		source: ConnectorSource.TUYA,
		name: 'Tuya',
		description: 'FastyBird IoT connector for Tuya devices',
		links: {
			documentation: 'http://www.fastybird.com',
			devDocumentation: 'http://www.fastybird.com',
			bugsTracking: 'http://www.fastybird.com',
		},
		components: {
			connectorDetail: undefined,
			addConnector: undefined,
			editConnector: undefined,
		},
		core: true,
	},
	{
		type: 'viera-connector',
		source: ConnectorSource.VIERA,
		name: 'Viera',
		description: 'FastyBird IoT connector for Panasonic Viera televisions',
		links: {
			documentation: 'http://www.fastybird.com',
			devDocumentation: 'http://www.fastybird.com',
			bugsTracking: 'http://www.fastybird.com',
		},
		components: {
			connectorDetail: undefined,
			addConnector: undefined,
			editConnector: undefined,
		},
		core: true,
	},
	{
		type: 'zigbee2mqtt-connector',
		source: ConnectorSource.ZIGBEE2MQTT,
		name: 'Zigbee2MQTT',
		description: 'FastyBird IoT connector for Zigbee2MQTT devices',
		links: {
			documentation: 'http://www.fastybird.com',
			devDocumentation: 'http://www.fastybird.com',
			bugsTracking: 'http://www.fastybird.com',
		},
		components: {
			connectorDetail: undefined,
			addConnector: undefined,
			editConnector: undefined,
		},
		core: true,
	},
];
