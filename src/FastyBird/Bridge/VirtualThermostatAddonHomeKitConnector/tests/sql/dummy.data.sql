INSERT
IGNORE INTO `fb_devices_module_connectors` (`connector_id`, `connector_identifier`, `connector_name`, `connector_comment`, `connector_enabled`, `connector_type`, `created_at`, `updated_at`) VALUES
(_binary 0x2b1ce81f99334d52afd4bec3583e6a06, 'virtual', 'Virtual', null, true, 'virtual-connector', '2024-02-04 22:00:00', '2024-02-04 22:00:00'),
(_binary 0xbda37bc79bd74083a925386ac5522325, 'universal-test-connector', 'Testing connector', null, true, 'dummy', '2024-02-04 22:00:00', '2024-02-04 22:00:00'),
(_binary 0x451ab010f5004eff82899ed09e56a887, 'homekit', 'HomeKit', null, true, 'homekit-connector', '2024-02-04 22:00:00', '2024-02-04 22:00:00');

INSERT
IGNORE INTO `fb_devices_module_connectors_controls` (`control_id`, `connector_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0xe7c9e5834af14b86b647f179207e6456, _binary 0x2b1ce81f99334d52afd4bec3583e6a06, 'reboot', '2024-02-04 22:00:00', '2024-02-04 22:00:00');

INSERT
IGNORE INTO `fb_devices_module_devices` (`device_id`, `connector_id`, `device_category`, `device_identifier`, `device_name`, `device_comment`, `params`, `created_at`, `updated_at`, `owner`, `device_type`) VALUES
(_binary 0x552cea8a0e8141d9be2f839b079f315e, _binary 0x2b1ce81f99334d52afd4bec3583e6a06, 'generic', 'thermostat-office', 'Thermostat - Office', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', null, 'virtual-thermostat-addon'),
(_binary 0x8eab9ed0c3834941a3d97c501fe091b2, _binary 0x2b1ce81f99334d52afd4bec3583e6a06, 'generic', 'thermostat-living-room', 'Thermostat - Living Room', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', null, 'virtual-thermostat-addon'),
(_binary 0x495a7b6804284bdcb098dca416f03363, _binary 0xbda37bc79bd74083a925386ac5522325, 'generic', 'universal-test-device-one', 'Actor & Sensor device', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', null, 'dummy'),
(_binary 0xe10c43a9fa3b463e983104ba23025479, _binary 0xbda37bc79bd74083a925386ac5522325, 'generic', 'universal-test-device-two', 'Actor & Sensor device', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', null, 'dummy'),
(_binary 0xcfb6e9cc29a748d6aecb5f901d79eba2, _binary 0x451ab010f5004eff82899ed09e56a887, 'generic', 'thermostat-office', 'Thermostat - Office', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', null, 'virtual-thermostat-addon-bridge'),
(_binary 0x45716d9eb19446a98dca5a66cc12a996, _binary 0x451ab010f5004eff82899ed09e56a887, 'generic', 'thermostat-living-room', 'Thermostat - Living Room - Corrupted', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', null, 'virtual-thermostat-addon-bridge');

INSERT
IGNORE INTO `fb_devices_module_devices_children` (`parent_device`, `child_device`) VALUES
(_binary 0x552cea8a0e8141d9be2f839b079f315e, _binary 0xcfb6e9cc29a748d6aecb5f901d79eba2),
(_binary 0x8eab9ed0c3834941a3d97c501fe091b2, _binary 0x45716d9eb19446a98dca5a66cc12a996);

INSERT
IGNORE INTO `fb_devices_module_devices_properties` (`property_id`, `device_id`, `parent_id`, `property_category`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_step`, `property_value`, `property_default`, `params`, `created_at`, `updated_at`, `property_type`) VALUES
(_binary 0x580a6d7c45174821a6562810ae876898, _binary 0x552cea8a0e8141d9be2f839b079f315e, null, 'generic', 'hardware_model', null, 0, 0, 'string', null, null, null, null, null, 'virtual-thermostat', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'variable'),
(_binary 0xc24e65c8610a437db7097de3127695e3, _binary 0x552cea8a0e8141d9be2f839b079f315e, null, 'generic', 'state', null, 0, 0, 'enum', null, 'connected,disconnected,alert,unknown', null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dynamic'),
(_binary 0x4412b0eaafba41c3a9d07d3b04abf61c, _binary 0x552cea8a0e8141d9be2f839b079f315e, null, 'generic', 'hardware_mac_address', null, 0, 0, 'string', null, null, null, null, null, '9f:c7:60:c3:c8:bd:64', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'variable'),
(_binary 0xdb3dc98d0ea744ad8be3397dd48f62b4, _binary 0x552cea8a0e8141d9be2f839b079f315e, null, 'generic', 'hardware_manufacturer', null, 0, 0, 'string', null, null, null, null, null, 'FastyBird', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'variable'),
(_binary 0x9302a6145d3d466bb832b6abd7e77ec6, _binary 0xcfb6e9cc29a748d6aecb5f901d79eba2, null, 'generic', 'category', null, 0, 0, 'string', null, null, null, null, null, '9', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'variable'),
(_binary 0x1da711c4ad894619bccc9eed96d9ea7f, _binary 0xcfb6e9cc29a748d6aecb5f901d79eba2, null, 'generic', 'hardware_model', null, 0, 0, 'string', null, null, null, null, null, 'Virtual Thermostat Bridge', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'variable'),
(_binary 0xf4926d6197f3470cb6a6ab2fe4a139aa, _binary 0xcfb6e9cc29a748d6aecb5f901d79eba2, null, 'generic', 'firmware_manufacturer', null, 0, 0, 'string', null, null, null, null, null, 'FastyBird', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'variable'),
(_binary 0x3470d84edcf1459081767a3b228e27ab, _binary 0x45716d9eb19446a98dca5a66cc12a996, null, 'generic', 'category', null, 0, 0, 'string', null, null, null, null, null, '9', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'variable'),
(_binary 0x936b090b2661408e828d51a5c20480b9, _binary 0x45716d9eb19446a98dca5a66cc12a996, null, 'generic', 'hardware_model', null, 0, 0, 'string', null, null, null, null, null, 'Virtual Thermostat Bridge', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'variable');

INSERT
IGNORE INTO `fb_devices_module_channels` (`channel_id`, `device_id`, `channel_category`, `channel_identifier`, `channel_name`, `channel_comment`, `params`, `created_at`, `updated_at`, `channel_type`) VALUES
(_binary 0xc2c572b3324844daaca0fd329e1d9418, _binary 0x552cea8a0e8141d9be2f839b079f315e, 'generic', 'configuration', null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'virtual-thermostat-addon-configuration'),
(_binary 0xb453987ebbf446fc830f6448b19d9665, _binary 0x552cea8a0e8141d9be2f839b079f315e, 'generic', 'state', null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'virtual-thermostat-addon-state'),
(_binary 0x29e4d707142d422499830e568f259639, _binary 0x552cea8a0e8141d9be2f839b079f315e, 'generic', 'sensors', null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'virtual-thermostat-addon-sensors'),
(_binary 0x9808b3869ed44e5888f1b39f5f70ef39, _binary 0x552cea8a0e8141d9be2f839b079f315e, 'generic', 'actors', null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'virtual-thermostat-addon-actors'),
(_binary 0x62f00da6eff74ce6ac1c81523627987e, _binary 0x552cea8a0e8141d9be2f839b079f315e, 'generic', 'preset_manual', null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'virtual-thermostat-addon-preset'),
(_binary 0x32c0bd12cf354bf6a22a3b8212f7cd78, _binary 0x8eab9ed0c3834941a3d97c501fe091b2, 'generic', 'configuration', null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'virtual-thermostat-addon-configuration'),
(_binary 0xfc13f0bb11de4f12a923c769f4847afc, _binary 0x8eab9ed0c3834941a3d97c501fe091b2, 'generic', 'state', null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'virtual-thermostat-addon-state'),
(_binary 0xf016fa96bfcb4f3cb661b82d59cc1b20, _binary 0x8eab9ed0c3834941a3d97c501fe091b2, 'generic', 'sensors', null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'virtual-thermostat-addon-sensors'),
(_binary 0x4037033b90184cf08b18e0ddcc34267f, _binary 0x8eab9ed0c3834941a3d97c501fe091b2, 'generic', 'actors', null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'virtual-thermostat-addon-actors'),
(_binary 0xc55dcc2f43c84f03862ea5a2c5ba91c4, _binary 0x8eab9ed0c3834941a3d97c501fe091b2, 'generic', 'preset_manual', null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'virtual-thermostat-addon-preset'),
(_binary 0x6ecec6b9a48a48918d61d552e63e5f5a, _binary 0x495a7b6804284bdcb098dca416f03363, 'generic', 'thermometer', 'Heating element', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dummy'),
(_binary 0x0df26d9652954b1084d36dc0f626e9bb, _binary 0xe10c43a9fa3b463e983104ba23025479, 'generic', 'thermometer', 'Heating element', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dummy'),
(_binary 0xaf9c9d5869c54d309bceca6e6ce4ed45, _binary 0xcfb6e9cc29a748d6aecb5f901d79eba2, 'generic', 'thermostat_1', 'Thermostat Service', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'virtual-thermostat-addon-bridge'),
(_binary 0xb6bd042ea8524752bd123c6e9c8a96a4, _binary 0x45716d9eb19446a98dca5a66cc12a996, 'generic', 'thermostat_1', 'Thermostat Service', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'virtual-thermostat-addon-bridge');

INSERT
IGNORE INTO `fb_devices_module_channels_properties` (`property_id`, `channel_id`, `parent_id`, `property_category`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_step`, `property_value`, `property_default`, `params`, `created_at`, `updated_at`, `property_type`) VALUES
(_binary 0x9c5e5a5f1b5d4394a9b9199f1701efac, _binary 0x6ecec6b9a48a48918d61d552e63e5f5a, null, 'generic', 'opening_sensor', 'Temperature', 0, 1, 'float', '°C', null, null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dynamic'),
(_binary 0x1e196c5ca4694ec795e7c4bb48d58fe0, _binary 0x6ecec6b9a48a48918d61d552e63e5f5a, null, 'generic', 'floor_sensor', 'Floor temperature', 0, 1, 'float', '°C', null, null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dynamic'),
(_binary 0x11807caa082c468b8a1b88ba8a715ca1, _binary 0x6ecec6b9a48a48918d61d552e63e5f5a, null, 'generic', 'heater', 'Switch', 1, 1, 'bool', null, null, null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dynamic'),
(_binary 0x13485858344f4affa196bd2db828cd7b, _binary 0x0df26d9652954b1084d36dc0f626e9bb, null, 'generic', 'opening_sensor', 'Temperature', 0, 1, 'float', '°C', null, null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dynamic'),
(_binary 0x8c6254282b9441679ec53997dd95c37a, _binary 0x0df26d9652954b1084d36dc0f626e9bb, null, 'generic', 'floor_sensor', 'Floor temperature', 0, 1, 'float', '°C', null, null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dynamic'),
(_binary 0x11444ab2a0654d1c9e8f3dff818ff256, _binary 0x0df26d9652954b1084d36dc0f626e9bb, null, 'generic', 'heater', 'Switch', 1, 1, 'bool', null, null, null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dynamic'),
(_binary 0xf0b8100f5ddb4abd8015d0dbf9a11aa0, _binary 0xb453987ebbf446fc830f6448b19d9665, null, 'generic', 'preset_mode', null, 0, 1, 'enum', null, 'manual', null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dynamic'),
(_binary 0xa74d06a48eb2440e8bb06ec54a0bf93c, _binary 0xb453987ebbf446fc830f6448b19d9665, null, 'generic', 'current_room_temperature', null, 0, 1, 'float', null, '7:35', null, null, 0.1, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dynamic'),
(_binary 0x17627f14ebbf4bc188fde8fc32d3e5de, _binary 0x62f00da6eff74ce6ac1c81523627987e, null, 'generic', 'target_room_temperature', null, 1, 1, 'float', null, '7:35', null, null, 0.1, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dynamic'),
(_binary 0xa326ba38d1884eaca6ad43bdcc84a730, _binary 0xb453987ebbf446fc830f6448b19d9665, null, 'generic', 'hvac_mode', null, 1, 1, 'enum', null, 'off,heat', null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dynamic'),
(_binary 0xc07dbed51a6d4a9cbdc831fe5b77cccd, _binary 0xc2c572b3324844daaca0fd329e1d9418, null, 'generic', 'high_target_temperature_tolerance', null, 0, 0, 'float', null, null, null, null, 0.1, '0.3', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'variable'),
(_binary 0x04cd4f8fe8a640a3b2ef33f574988ee8, _binary 0xb453987ebbf446fc830f6448b19d9665, null, 'generic', 'hvac_state', null, 0, 1, 'enum', null, 'off,heating', null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dynamic'),
(_binary 0xb814bc0c9ce54f2d82ccf21e6b0e20f0, _binary 0xc2c572b3324844daaca0fd329e1d9418, null, 'generic', 'low_target_temperature_tolerance', null, 0, 0, 'float', null, null, null, null, 0.1, '0.3', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'variable'),
(_binary 0x976a5face3a6425d8cdb4a0df222766f, _binary 0xfc13f0bb11de4f12a923c769f4847afc, null, 'generic', 'preset_mode', null, 0, 1, 'enum', null, 'manual', null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dynamic'),
(_binary 0x2f6b6be45d4b435e8e475175a10d2776, _binary 0xfc13f0bb11de4f12a923c769f4847afc, null, 'generic', 'current_room_temperature', null, 0, 1, 'float', null, '7:35', null, null, 0.1, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dynamic'),
(_binary 0xed77fd9613e04e2ebe089da7ba86b5d9, _binary 0xc55dcc2f43c84f03862ea5a2c5ba91c4, null, 'generic', 'target_room_temperature', null, 1, 1, 'float', null, '7:35', null, null, 0.1, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dynamic'),
(_binary 0xc0f3d3e2ffad4d2784a53d237dda6ff4, _binary 0xfc13f0bb11de4f12a923c769f4847afc, null, 'generic', 'hvac_mode', null, 1, 1, 'enum', null, 'off,heat', null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dynamic'),
(_binary 0x4ac7b19fbfa6472396b26461658168b5, _binary 0x32c0bd12cf354bf6a22a3b8212f7cd78, null, 'generic', 'high_target_temperature_tolerance', null, 0, 0, 'float', null, null, null, null, 0.1, '0.3', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'variable'),
(_binary 0x132b294beb4a438eb6ffe213c97b0b70, _binary 0xfc13f0bb11de4f12a923c769f4847afc, null, 'generic', 'hvac_state', null, 0, 1, 'enum', null, 'off,heating', null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dynamic'),
(_binary 0xd9cdcd08c822487f8a8f4c88ea2b50d1, _binary 0x32c0bd12cf354bf6a22a3b8212f7cd78, null, 'generic', 'low_target_temperature_tolerance', null, 0, 0, 'float', null, null, null, null, 0.1, '0.3', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'variable'),
(_binary 0xd58fe8940d1c4bf0bff5a190cab20e5c, _binary 0x29e4d707142d422499830e568f259639, _binary 0x9c5e5a5f1b5d4394a9b9199f1701efac, 'generic', 'room_temperature_sensor_1', null, 0, 1, 'float', null, null, null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'mapped'),
(_binary 0xe2b982612a05483dbe7cac3afe3888b2, _binary 0x29e4d707142d422499830e568f259639, _binary 0x1e196c5ca4694ec795e7c4bb48d58fe0, 'generic', 'floor_temperature_sensor_1', null, 0, 1, 'float', null, null, null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'mapped'),
(_binary 0xbceca5432de744b18a3387e9574b6731, _binary 0x9808b3869ed44e5888f1b39f5f70ef39, _binary 0x11807caa082c468b8a1b88ba8a715ca1, 'generic', 'heater_actor_1', null, 1, 1, 'bool', null, null, null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'mapped'),
(_binary 0xd963d04bea184c0b99e04a01ef3b9f00, _binary 0xf016fa96bfcb4f3cb661b82d59cc1b20, _binary 0x13485858344f4affa196bd2db828cd7b, 'generic', 'room_temperature_sensor_1', null, 0, 1, 'float', null, null, null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'mapped'),
(_binary 0x08d9bfaad19242ec903b5dc2922d8ad2, _binary 0xf016fa96bfcb4f3cb661b82d59cc1b20, _binary 0x8c6254282b9441679ec53997dd95c37a, 'generic', 'floor_temperature_sensor_1', null, 0, 1, 'float', null, null, null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'mapped'),
(_binary 0x8d2322dc9e4e46349707f5a1111c24e2, _binary 0x4037033b90184cf08b18e0ddcc34267f, _binary 0x11444ab2a0654d1c9e8f3dff818ff256, 'generic', 'heater_actor_1', null, 1, 1, 'bool', null, null, null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'mapped'),
(_binary 0xe670e0a558584fb6afc203115455bd67, _binary 0xcfb6e9cc29a748d6aecb5f901d79eba2, _binary 0x04cd4f8fe8a640a3b2ef33f574988ee8, 'generic', 'current_heating_cooling_state', null, 1, 0, 'enum', null, 'off:0:0,heating:1:1', null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'mapped'),
(_binary 0x702abf59129643409337ca5370e1d5ee, _binary 0xcfb6e9cc29a748d6aecb5f901d79eba2, _binary 0xa326ba38d1884eaca6ad43bdcc84a730, 'generic', 'target_heating_cooling_state', null, 1, 0, 'enum', null, 'off:0:0,heat:1:1', null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'mapped'),
(_binary 0xe4b1af5016744ee487ccde85bfabae79, _binary 0xcfb6e9cc29a748d6aecb5f901d79eba2, _binary 0xa74d06a48eb2440e8bb06ec54a0bf93c, 'generic', 'current_temperature', null, 1, 0, 'float', null, '7:35', null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'mapped'),
(_binary 0x0455176d22b0422296a37b691a5a326b, _binary 0xcfb6e9cc29a748d6aecb5f901d79eba2, _binary 0x17627f14ebbf4bc188fde8fc32d3e5de, 'generic', 'target_temperature', null, 1, 0, 'float', null, '7:35', null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'mapped'),
(_binary 0x8c105aa844ea4c27903cc67dd679d1ba, _binary 0xcfb6e9cc29a748d6aecb5f901d79eba2, null, 'generic', 'TemperatureDisplayUnits', null, 1, 0, 'enum', null, '0,1', null, null, null, null, null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'dynamic'),
(_binary 0x16c60f151183421ea6f08e7b9ab50ce0, _binary 0xcfb6e9cc29a748d6aecb5f901d79eba2, null, 'generic', 'name', null, 0, 0, 'string', null, null, null, null, null, 'Office Thermostat', null, null, '2024-02-04 22:00:00', '2024-02-04 22:00:00', 'variable');

INSERT
IGNORE INTO `fb_security_policies` (`policy_id`, `policy_type`, `policy_v0`, `policy_v1`, `policy_v2`, `policy_v3`, `policy_v4`, `policy_v5`, `policy_policy_type`) VALUES
(_binary 0xb12082a40f5f4f3fb4e8c0d0ca209613, 'p', 'administrator', null, null, null, null, null, 'policy'),
(_binary 0xf5fc546183c94344ab43824619faaa91, 'p', 'manager', null, null, null, null, null, 'policy'),
(_binary 0x110e9e3b0d724827974906f89c368bc1, 'p', 'user', null, null, null, null, null, 'policy'),
(_binary 0x46f0644f91eb4877a3b032ab29189794, 'p', 'visitor', null, null, null, null, null, 'policy'),
(_binary 0x253fcd3a59b847288d6d017b02bc10e2, 'g', '5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34', 'administrator', null, null, null, null, 'policy'),
(_binary 0x15592686e28a4208bc1dc7134ff472cc, 'g', 'efbfbdef-bfbd-68ef-bfbd-770b40efbfbd', 'user', null, null, null, null, 'policy');
