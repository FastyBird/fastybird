interface IRoutes {
  root: string

  devices: string
  deviceDetail: string
  deviceSettings: string
  deviceSettingsAddChannel: string
  deviceSettingsEditChannel: string

  connectors: string
  connectorDetail: string
  connectorSettings: string
  connectorSettingsAddDevice: string
  connectorSettingsEditDevice: string
  connectorSettingsEditDeviceAddChannel: string
  connectorSettingsEditDeviceEditChannel: string
}

export function useRoutesNames(): { routeNames: IRoutes } {
  const routeNames: IRoutes = {
    root: 'devices_module-root',

    devices: 'devices_module-devices',
    deviceDetail: 'devices_module-device_detail',
    deviceSettings: 'devices_module-device_settings',
    deviceSettingsAddChannel: 'devices_module-device_settings_add_channel',
    deviceSettingsEditChannel: 'devices_module-device_settings_edit_channel',

    connectors: 'devices_module-connectors',
    connectorDetail: 'devices_module-connector_detail',
    connectorSettings: 'devices_module-connector_settings',
    connectorSettingsAddDevice: 'devices_module-connector_settings_add_device',
    connectorSettingsEditDevice: 'devices_module-connector_settings_edit_device',
    connectorSettingsEditDeviceAddChannel: 'devices_module-connector_settings_edit_device_add_channel',
    connectorSettingsEditDeviceEditChannel: 'devices_module-connector_settings_edit_device_edit_channel',
  }

  return {
    routeNames
  }
}
