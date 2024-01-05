INSERT
IGNORE INTO `fb_devices_module_connectors` (`connector_id`, `connector_identifier`, `connector_name`, `connector_comment`, `connector_enabled`, `connector_type`, `created_at`, `updated_at`) VALUES
(_binary 0xf15d2072fb60421aa85f2566e4dc13fe, 'zigbee2mqtt', 'Zigbee2MQTT', null, true, 'zigbee2mqtt', '2023-12-23 20:00:00', '2023-12-23 20:00:00');

INSERT
IGNORE INTO `fb_devices_module_connectors_properties` (`property_id`, `connector_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x0a7d3ba9d7e124f7da100eecbed52c567, _binary 0xf15d2072fb60421aa85f2566e4dc13fe, 'variable', 'mode', NULL, 0, 0, 'string', NULL, NULL, NULL, NULL, 'mqtt', '2023-12-23 20:00:00', '2023-12-23 20:00:00');

INSERT
IGNORE INTO `fb_devices_module_connectors_controls` (`control_id`, `connector_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0xb84805daea89409f8cbc4454f97a5653, _binary 0xf15d2072fb60421aa85f2566e4dc13fe, 'reboot', '2023-12-23 20:00:00', '2023-12-23 20:00:00'),
(_binary 0x81586b0dd7ec4778ae300de3e7f7d4da, _binary 0xf15d2072fb60421aa85f2566e4dc13fe, 'discover', '2023-12-23 20:00:00', '2023-12-23 20:00:00');

INSERT
IGNORE INTO `fb_devices_module_devices` (`device_id`, `device_type`, `device_identifier`, `device_name`, `device_comment`, `params`, `created_at`, `updated_at`, `owner`, `connector_id`) VALUES
(_binary 0xc9cdc7c29ae0433993b718530aec0c42, 'zigbee2mqtt-bridge', 'bridge', 'Zigbee2MQTT Bridge', NULL, NULL, '2020-03-19 14:03:48', '2020-03-22 20:12:07', '455354e8-96bd-4c29-84e7-9f10e1d4db4b', _binary 0xf15d2072fb60421aa85f2566e4dc13fe);

INSERT
IGNORE INTO `fb_devices_module_devices_properties` (`property_id`, `device_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x03042ff4762641518e2d8f4a68517435, _binary 0xc9cdc7c29ae0433993b718530aec0c42, 'dynamic', 'state', 'state', 0, 0, 'enum', NULL, 'connected,disconnected,alert,unknown', NULL, NULL, NULL, '2023-12-23 20:00:00', '2023-12-23 20:00:00'),
(_binary 0x2a1341932a8346cd89c07f346346d1b2, _binary 0xc9cdc7c29ae0433993b718530aec0c42, 'variable', 'base_topic', 'base_topic', 0, 1, 'string', NULL, NULL, NULL, NULL, 'zigbee2mqtt', '2023-12-23 20:00:00', '2023-12-23 20:00:00');
