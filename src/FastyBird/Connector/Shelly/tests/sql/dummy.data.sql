INSERT
IGNORE INTO `fb_devices_module_connectors` (`connector_id`, `connector_identifier`, `connector_name`, `connector_comment`, `connector_enabled`, `connector_type`, `created_at`, `updated_at`) VALUES
(_binary 0x86d6a4ba62924947b8c18681569ac28e, 'shelly-local', 'Shelly Local', null, true, 'shelly-connector', '2023-09-04 10:00:00', '2023-09-04 10:00:00');

INSERT
IGNORE INTO `fb_devices_module_connectors_controls` (`control_id`, `connector_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0xf56f419d79724d8b8f253c75e34c1c42, _binary 0x86d6a4ba62924947b8c18681569ac28e, 'reboot', '2023-09-04 10:00:00', '2023-09-04 10:00:00'),
(_binary 0x40cc4a777fbf45e8b3c49fccc8203cb4, _binary 0x86d6a4ba62924947b8c18681569ac28e, 'discover', '2023-09-04 10:00:00', '2023-09-04 10:00:00');

INSERT
IGNORE INTO `fb_devices_module_connectors_properties` (`property_id`, `connector_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0xca00cb856d60456ca29abc1538e8b683, _binary 0x86d6a4ba62924947b8c18681569ac28e, 'variable', 'mode', 'Mode', 0, 0, 'string', NULL, NULL, NULL, NULL, 'local', '2023-09-04 10:00:00', '2023-09-04 10:00:00');

INSERT
IGNORE INTO `fb_security_policies` (`policy_id`, `policy_type`, `policy_v0`, `policy_v1`, `policy_v2`, `policy_v3`, `policy_v4`, `policy_v5`, `policy_policy_type`) VALUES
(_binary 0xb12082a40f5f4f3fb4e8c0d0ca209613, 'p', 'administrator', null, null, null, null, null, 'policy'),
(_binary 0xf5fc546183c94344ab43824619faaa91, 'p', 'manager', null, null, null, null, null, 'policy'),
(_binary 0x110e9e3b0d724827974906f89c368bc1, 'p', 'user', null, null, null, null, null, 'policy'),
(_binary 0x46f0644f91eb4877a3b032ab29189794, 'p', 'visitor', null, null, null, null, null, 'policy'),
(_binary 0x253fcd3a59b847288d6d017b02bc10e2, 'g', '5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34', 'administrator', null, null, null, null, 'policy'),
(_binary 0x15592686e28a4208bc1dc7134ff472cc, 'g', 'efbfbdef-bfbd-68ef-bfbd-770b40efbfbd', 'user', null, null, null, null, 'policy');
