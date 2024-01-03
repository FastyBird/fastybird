INSERT
IGNORE INTO `fb_devices_module_connectors` (`connector_id`, `connector_identifier`, `connector_name`, `connector_comment`, `connector_enabled`, `connector_type`, `created_at`, `updated_at`) VALUES
(_binary 0x0640b179ee924766a49170e02e9e2378, 'zigbee2mqtt', 'Zigbee2MQTT', null, true, 'zigbee2mqtt', '2023-12-23 20:00:00', '2023-12-23 20:00:00');

INSERT
IGNORE INTO `fb_devices_module_connectors_properties` (`property_id`, `connector_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x04364753cad341668c7659a732d2d38c, _binary 0x0640b179ee924766a49170e02e9e2378, 'variable', 'mode', NULL, 0, 0, 'string', NULL, NULL, NULL, NULL, 'mqtt', '2023-12-23 20:00:00', '2023-12-23 20:00:00');

INSERT
IGNORE INTO `fb_devices_module_connectors_controls` (`control_id`, `connector_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0x4d125b6234b34f20bdfec96a8fcbccba, _binary 0x0640b179ee924766a49170e02e9e2378, 'reboot', '2023-12-23 20:00:00', '2023-12-23 20:00:00'),
(_binary 0x989e46f6fb094a16b2209860446dc317, _binary 0x0640b179ee924766a49170e02e9e2378, 'discover', '2023-12-23 20:00:00', '2023-12-23 20:00:00');

INSERT
IGNORE INTO `fb_devices_module_devices` (`device_id`, `device_type`, `device_identifier`, `device_name`, `device_comment`, `params`, `created_at`, `updated_at`, `owner`, `connector_id`) VALUES
(_binary 0xf74fb1e5d02c4024849e11d2dbfd4c71, 'zigbee2mqtt-bridge', 'bridge', 'Zigbee2MQTT Bridge', NULL, NULL, '2020-03-19 14:03:48', '2020-03-22 20:12:07', '455354e8-96bd-4c29-84e7-9f10e1d4db4b', _binary 0x0640b179ee924766a49170e02e9e2378);

INSERT
IGNORE INTO `fb_devices_module_devices_properties` (`property_id`, `device_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0xdc28f00d8e4d41a69666f95753f30c50, _binary 0xf74fb1e5d02c4024849e11d2dbfd4c71, 'dynamic', 'state', 'state', 0, 0, 'enum', NULL, 'connected,disconnected,alert,unknown', NULL, NULL, NULL, '2023-12-23 20:00:00', '2023-12-23 20:00:00'),
(_binary 0x26d945a714e849e6ac7ff6fe76881285, _binary 0xf74fb1e5d02c4024849e11d2dbfd4c71, 'variable', 'base_topic', 'base_topic', 0, 1, 'string', NULL, NULL, NULL, NULL, 'zigbee2mqtt', '2023-12-23 20:00:00', '2023-12-23 20:00:00');
