INSERT
IGNORE INTO `fb_devices_module_connectors` (`connector_id`, `connector_identifier`, `connector_name`, `connector_comment`, `connector_enabled`, `connector_type`, `created_at`, `updated_at`) VALUES
(_binary 0xedf4ff52babc43f59e721f38c35e73f0, 'sonoff-cloud', 'Sonoff Cloud', null, true, 'sonoff-connector', '2023-10-10 20:00:00', '2023-10-10 20:00:00'),
(_binary 0x63a28696cbea475784d7156e52ade4d3, 'sonoff-local', 'Sonoff Local', null, true, 'sonoff-connector', '2023-10-10 20:00:00', '2023-10-10 20:00:00');

INSERT
IGNORE INTO `fb_devices_module_connectors_controls` (`control_id`, `connector_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0xc03f29d555ecd4b97a8ebb3ed23e98ce2, _binary 0xedf4ff52babc43f59e721f38c35e73f0, 'reboot', '2023-10-10 20:00:00', '2023-10-10 20:00:00'),
(_binary 0x4700438678674c5790eea5976586490d, _binary 0xedf4ff52babc43f59e721f38c35e73f0, 'discover', '2023-10-10 20:00:00', '2023-10-10 20:00:00'),
(_binary 0x0a0cf4bec6fc48f09399da2113ac867c, _binary 0x63a28696cbea475784d7156e52ade4d3, 'reboot', '2023-10-10 20:00:00', '2023-10-10 20:00:00'),
(_binary 0x325ccf724d314da9a48742986be45a9c, _binary 0x63a28696cbea475784d7156e52ade4d3, 'discover', '2023-10-10 20:00:00', '2023-10-10 20:00:00');

INSERT
IGNORE INTO `fb_devices_module_connectors_properties` (`property_id`, `connector_id`, `property_type`, `property_identifier`, `property_name`, `property_settable`, `property_queryable`, `property_data_type`, `property_unit`, `property_format`, `property_invalid`, `property_scale`, `property_value`, `created_at`, `updated_at`) VALUES
(_binary 0xd913ac6ccb6d423184827f7cfb5d36f5, _binary 0xedf4ff52babc43f59e721f38c35e73f0, 'variable', 'mode', 'Mode', 0, 0, 'string', NULL, NULL, NULL, NULL, 'cloud', '2023-10-10 20:00:00', '2023-10-10 20:00:00'),
(_binary 0x6c87b66afcb3479ea6d2ebb1557886d5, _binary 0xedf4ff52babc43f59e721f38c35e73f0, 'variable', 'username', 'Username', 0, 0, 'string', NULL, NULL, NULL, NULL, 'user@username.com', '2023-10-10 20:00:00', '2023-10-10 20:00:00'),
(_binary 0xb21c9c28c3f5494b874699d8dd4568a2, _binary 0xedf4ff52babc43f59e721f38c35e73f0, 'variable', 'password', 'Password', 0, 0, 'string', NULL, NULL, NULL, NULL, 'dBCQZohQNR2U4rW9', '2023-10-10 20:00:00', '2023-10-10 20:00:00'),
(_binary 0x1ad67aa5cc344c94b0f7a2ebe099a71c, _binary 0x63a28696cbea475784d7156e52ade4d3, 'variable', 'mode', 'Mode', 0, 0, 'string', NULL, NULL, NULL, NULL, 'lan', '2023-10-10 20:00:00', '2023-10-10 20:00:00'),
(_binary 0xbf424644ae94483a8474d8d539311c7d, _binary 0x63a28696cbea475784d7156e52ade4d3, 'variable', 'username', 'Username', 0, 0, 'string', NULL, NULL, NULL, NULL, 'other@username.com', '2023-10-10 20:00:00', '2023-10-10 20:00:00'),
(_binary 0x0b78122520d34240a22927a1dbc14dac, _binary 0x63a28696cbea475784d7156e52ade4d3, 'variable', 'password', 'Password', 0, 0, 'string', NULL, NULL, NULL, NULL, 'dBCQZohQNR2U4rW9', '2023-10-10 20:00:00', '2023-10-10 20:00:00');

INSERT
IGNORE INTO `fb_security_policies` (`policy_id`, `policy_type`, `policy_v0`, `policy_v1`, `policy_v2`, `policy_v3`, `policy_v4`, `policy_v5`, `policy_policy_type`) VALUES
(_binary 0xb12082a40f5f4f3fb4e8c0d0ca209613, 'p', 'administrator', null, null, null, null, null, 'policy'),
(_binary 0xf5fc546183c94344ab43824619faaa91, 'p', 'manager', null, null, null, null, null, 'policy'),
(_binary 0x110e9e3b0d724827974906f89c368bc1, 'p', 'user', null, null, null, null, null, 'policy'),
(_binary 0x46f0644f91eb4877a3b032ab29189794, 'p', 'visitor', null, null, null, null, null, 'policy'),
(_binary 0x253fcd3a59b847288d6d017b02bc10e2, 'g', '5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34', 'administrator', null, null, null, null, 'policy'),
(_binary 0x15592686e28a4208bc1dc7134ff472cc, 'g', 'efbfbdef-bfbd-68ef-bfbd-770b40efbfbd', 'user', null, null, null, null, 'policy');
