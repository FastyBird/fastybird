INSERT
IGNORE INTO `fb_devices_module_connectors` (`connector_id`, `connector_identifier`, `connector_name`, `connector_comment`, `connector_enabled`, `connector_type`, `created_at`, `updated_at`) VALUES
(_binary 0x0640b179ee924766a49170e02e9e2378, 'zigbee2mqtt', 'Zigbee2MQTT', null, true, 'zigbee2mqtt', '2023-12-23 20:00:00', '2023-12-23 20:00:00');

INSERT
IGNORE INTO `fb_devices_module_connectors_properties` (`property_id`, `connector_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x04364753cad341668c7659a732d2d38c, _binary 0x0640b179ee924766a49170e02e9e2378, 'static', 'protocol', NULL, 0, 0, 'string', NULL, NULL, NULL, NULL, 'v1', '2023-12-23 20:00:00', '2023-12-23 20:00:00');

INSERT
IGNORE INTO `fb_devices_module_devices` (`device_id`, `device_type`, `device_identifier`, `device_name`, `device_comment`, `params`, `created_at`, `updated_at`, `owner`, `connector_id`) VALUES
(_binary 0xf74fb1e5d02c4024849e11d2dbfd4c71, 'blank', 'first-device', 'First device', NULL, NULL, '2020-03-19 14:03:48', '2020-03-22 20:12:07', '455354e8-96bd-4c29-84e7-9f10e1d4db4b', _binary 0x38c719d664f349409eec5f6fbebe8e97);

INSERT
IGNORE INTO `fb_devices_module_devices_properties` (`property_id`, `device_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0xdc28f00d8e4d41a69666f95753f30c50, _binary 0xf74fb1e5d02c4024849e11d2dbfd4c71, 'dynamic', 'uptime', 'uptime', 0, 1, 'int', NULL, NULL, NULL, NULL, NULL, '2023-12-23 20:00:00', '2023-12-23 20:00:00'),
(_binary 0x26d945a714e849e6ac7ff6fe76881285, _binary 0xf74fb1e5d02c4024849e11d2dbfd4c71, 'dynamic', 'rssi', 'rssi', 0, 1, 'int', NULL, NULL, NULL, NULL, NULL, '2023-12-23 20:00:00', '2023-12-23 20:00:00'),
(_binary 0x903b3f8df3ad46e98e10579c609bad72, _binary 0xf74fb1e5d02c4024849e11d2dbfd4c71, 'static', 'status_led', 'status_led', 0, 0, 'enum', NULL, 'on,off', NULL, NULL, 'on', '2023-12-23 20:00:00', '2023-12-23 20:00:00'),
(_binary 0xdaf56470d1b74f0baab4245634bcc738, _binary 0xf74fb1e5d02c4024849e11d2dbfd4c71, 'static', 'username', 'username', 0, 0, 'string', NULL, NULL, NULL, NULL, 'device-username', '2023-12-23 20:00:00', '2023-12-23 20:00:00'),
(_binary 0xe6a9b7b99134420cbc4a8c9735aed166, _binary 0xf74fb1e5d02c4024849e11d2dbfd4c71, 'static', 'password', 'password', 0, 0, 'string', NULL, NULL, NULL, NULL, 'device-password', '2023-12-23 20:00:00', '2023-12-23 20:00:00');

INSERT
IGNORE INTO `fb_devices_module_devices_controls` (`control_id`, `device_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0x0c5e58ba7095434d84278d43a4f18073, _binary 0xf74fb1e5d02c4024849e11d2dbfd4c71, 'configure', '2023-12-23 20:00:00', '2023-12-23 20:00:00');

INSERT
IGNORE INTO `fb_devices_module_channels` (`channel_id`, `device_id`, `channel_type`, `channel_name`, `channel_comment`, `channel_identifier`, `params`, `created_at`, `updated_at`) VALUES
(_binary 0x38c719d664f349409eec5f6fbebe8e97, _binary 0xf74fb1e5d02c4024849e11d2dbfd4c71, 'channel', 'Channel one', NULL, 'channel-one', NULL, '2023-12-23 20:00:00', '2023-12-23 20:00:00'),
(_binary 0xf5539ea8307149c3bc61d16be05389f1, _binary 0xf74fb1e5d02c4024849e11d2dbfd4c71, 'channel', 'Channel two', NULL, 'channel-two', NULL, '2023-12-23 20:00:00', '2023-12-23 20:00:00');

INSERT
IGNORE INTO `fb_devices_module_channels_properties` (`property_id`, `channel_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x6670b941d95642c18033bf999e829e12, _binary 0x38c719d664f349409eec5f6fbebe8e97, 'dynamic', 'switch', 'switch', 1, 1, 'enum', NULL, 'on,off,toggle', NULL, NULL, NULL, '2023-12-23 20:00:00', '2023-12-23 20:00:00'),
(_binary 0x69981e0860a8479a9ed97f626acb32a6, _binary 0xf5539ea8307149c3bc61d16be05389f1, 'dynamic', 'temperature', 'temperature', 0, 1, 'float', '°C', NULL, 999, 1, NULL, '2023-12-23 20:00:00', '2023-12-23 20:00:00'),
(_binary 0x6dd7db95e4df4d9f9af4b7abce1ed2de, _binary 0xf5539ea8307149c3bc61d16be05389f1, 'dynamic', 'humidity', 'humidity', 0, 1, 'float', '%', NULL, 999, 2, NULL, '2023-12-23 20:00:00', '2023-12-23 20:00:00');

INSERT
IGNORE INTO `fb_devices_module_channels_controls` (`control_id`, `channel_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0xc02c6751e0034e44bf669c29f7d0a0d8, _binary 0x38c719d664f349409eec5f6fbebe8e97, 'configure', '2023-12-23 20:00:00', '2023-12-23 20:00:00'),
(_binary 0x05b9429784344dc8b2d1815d667ac5fe, _binary 0xf5539ea8307149c3bc61d16be05389f1, 'configure', '2023-12-23 20:00:00', '2023-12-23 20:00:00');
