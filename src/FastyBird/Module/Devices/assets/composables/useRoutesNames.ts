import { IRoutes } from '../types';

export function useRoutesNames(): IRoutes {
	return {
		root: 'devices_module-root',

		devices: 'devices_module-devices',
		deviceCreate: 'devices_module-device_create',
		deviceDetail: 'devices_module-device_detail',
		deviceSettings: 'devices_module-device_settings',

		channelCreate: 'devices_module-device_channel_create',
		channelDetail: 'devices_module-device_channel_detail',
		channelSettings: 'devices_module-device_channel_settings',

		plugins: 'devices_module-plugins',
		pluginInstall: 'devices_module-plugin_install',
		pluginDetail: 'devices_module-plugin_detail',

		connectorCreate: 'devices_module-connector_create',
		connectorDetail: 'devices_module-connector_detail',
		connectorSettings: 'devices_module-connector_settings',

		connectorDetailDeviceCreate: 'devices_module-connector_detail_device_create',
		connectorDetailDeviceDetail: 'devices_module-connector_detail_device_detail',
		connectorDetailDeviceSettings: 'devices_module-connector_detail_device_settings',

		connectorDetailDeviceDetailChannelCreate: 'devices_module-connector_detail_device_detail_channel_create',
		connectorDetailDeviceDetailChannelDetail: 'devices_module-connector_detail_device_detail_channel_detail',
		connectorDetailDeviceDetailChannelSettings: 'devices_module-connector_detail_device_detail_channel_settings',
	};
}
