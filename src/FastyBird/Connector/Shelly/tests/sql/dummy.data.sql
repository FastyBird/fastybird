INSERT
IGNORE INTO `fb_devices_module_connectors` (`connector_id`, `connector_identifier`, `connector_name`, `connector_comment`, `connector_enabled`, `connector_type`, `created_at`, `updated_at`) VALUES
(_binary 0x46c9986b5ff742b3be0cc309ea3f7c19, 'shelly-local', 'Shelly Local', null, true, 'tuya', '2023-09-04 10:00:00', '2023-09-04 10:00:00');

INSERT
IGNORE INTO `fb_devices_module_connectors_controls` (`control_id`, `connector_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0xdf7f7afcd2f74e18b6138e1343620de5, _binary 0x46c9986b5ff742b3be0cc309ea3f7c19, 'reboot', '2023-09-04 10:00:00', '2023-09-04 10:00:00'),
(_binary 0x0ff07b0aa6874f6c84158b3460a23efe, _binary 0x46c9986b5ff742b3be0cc309ea3f7c19, 'discover', '2023-09-04 10:00:00', '2023-09-04 10:00:00');

INSERT
IGNORE INTO `fb_devices_module_connectors_properties` (`property_id`, `connector_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0x67a1cda6a52a4cb8b5c0c6e037853fe9, _binary 0x46c9986b5ff742b3be0cc309ea3f7c19, 'variable', 'mode', 'Mode', 0, 0, 'string', NULL, NULL, NULL, NULL, 'local', '2023-09-04 10:00:00', '2023-09-04 10:00:00');
