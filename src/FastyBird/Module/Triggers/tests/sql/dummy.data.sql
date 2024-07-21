INSERT IGNORE INTO `fb_triggers_module_triggers` (`trigger_id`, `trigger_type`, `trigger_name`, `trigger_comment`, `trigger_enabled`, `created_at`, `updated_at`, `params`) VALUES
(_binary 0x0B48DFBCFAC2429288DC7981A121602D, 'automatic', 'Good Evening', NULL, 1, '2020-01-27 20:49:53', '2020-01-27 20:49:53', '[]'),
(_binary 0x1B17BCAAA19E45F098B456211CC648AE, 'automatic', 'Rise n\'Shine', NULL, 1, '2020-01-27 14:24:34', '2020-01-27 14:24:34', '[]'),
(_binary 0x2CEA2C1B47904D828A9F902C7155AB36, 'automatic', 'House keeping', NULL, 1, '2020-01-27 14:25:19', '2020-01-27 14:25:19', '[]'),
(_binary 0x421CA8E926C6463089BAC53AEA9BCB1E, 'manual', 'Movie Night', NULL, 1, '2020-01-27 14:25:54', '2020-01-27 14:27:15', '[]'),
(_binary 0xB8BB82F331E2406A96EDF99EBAF9947A, 'manual', 'Bubble Bath', NULL, 1, '2020-01-27 14:27:40', '2020-01-29 22:16:47', '[]'),
(_binary 0xC64BA1C40EDA4CAB87A04D634F7B67F4, 'manual', 'Good Night\'s Sleep', NULL, 1, '2020-01-27 14:28:17', '2020-01-27 14:28:17', '[]');

INSERT IGNORE INTO `fb_triggers_module_triggers_controls` (`control_id`, `trigger_id`, `control_name`, `created_at`, `updated_at`) VALUES
(_binary 0x7C055B2B60C3401793DBE9478D8AA662, _binary 0x421CA8E926C6463089BAC53AEA9BCB1E, 'trigger', '2020-01-27 14:25:54', '2020-01-27 14:25:54'),
(_binary 0xCFCA08FFD19948ED9F008C6B840A567A, _binary 0xB8BB82F331E2406A96EDF99EBAF9947A, 'trigger', '2020-01-27 14:27:40', '2020-01-27 14:27:40'),
(_binary 0x177D6FC719054FD9B847E2DA8189DD6A, _binary 0xC64BA1C40EDA4CAB87A04D634F7B67F4, 'trigger', '2020-01-27 14:28:17', '2020-01-27 14:28:17');

INSERT IGNORE INTO `fb_triggers_module_conditions` (`condition_id`, `trigger_id`, `created_at`, `updated_at`, `condition_type`, `condition_watch_item`, `condition_operator`, `condition_operand`) VALUES
(_binary 0x09C453B3C55F40508F1CB50F8D5728C2, _binary 0x1B17BCAAA19E45F098B456211CC648AE, '2020-01-27 14:24:34', '2020-01-27 14:24:34', 'dummy', _binary 0x51d05010c079494cba739ea206f6bad1, 'above', '3'),
(_binary 0x167900E919F34712AA4D00B160FF06D5, _binary 0x0B48DFBCFAC2429288DC7981A121602D, '2020-01-27 20:49:53', '2020-01-27 20:49:53', 'dummy', _binary 0xc104d793ff6c4758b97483e4b50478af, 'below', '3'),
(_binary 0x2726F19C7759440EB6F58C3306692FA2, _binary 0x2CEA2C1B47904D828A9F902C7155AB36, '2020-01-27 14:25:19', '2020-01-27 14:25:19', 'dummy', _binary 0x28989c89e7d746649d18a73647a844fb, 'eq', '3');

INSERT IGNORE INTO `fb_triggers_module_actions` (`action_id`, `trigger_id`, `action_type`, `created_at`, `updated_at`, `action_do_item`, `action_value`) VALUES
(_binary 0x21D13F148BE0462587644D5B1F3B4D1E, _binary 0x0B48DFBCFAC2429288DC7981A121602D, 'dummy', '2020-01-28 18:39:35', '2020-01-28 18:39:35', _binary 0xa830828c67684274b90920ce0e222347, 'on'),
(_binary 0x46C39A9539EB42169FA34D575A6295BD, _binary 0x421CA8E926C6463089BAC53AEA9BCB1E, 'dummy', '2020-01-27 14:25:54', '2020-01-27 14:25:54', _binary 0xa830828c67684274b90920ce0e222347, 'on'),
(_binary 0x4AA84028D8B7412895B2295763634AA4, _binary 0xC64BA1C40EDA4CAB87A04D634F7B67F4, 'dummy', '2020-01-27 14:28:17', '2020-01-27 14:28:17', _binary 0xa830828c67684274b90920ce0e222347, 'on'),
(_binary 0x52AA8A3518324317BE2C8B8FFFAAE07F, _binary 0xB8BB82F331E2406A96EDF99EBAF9947A, 'dummy', '2020-01-29 16:43:32', '2020-01-29 16:43:32', _binary 0xa830828c67684274b90920ce0e222347, 'on'),
(_binary 0x69CED64E6E5441E98052BA25E6199B25, _binary 0x2CEA2C1B47904D828A9F902C7155AB36, 'dummy', '2020-01-27 14:25:19', '2020-01-27 14:25:19', _binary 0x547b6fefc3a440b4bc99944f6340c907, 'off'),
(_binary 0x7B6398E4D26C4CB1BA0CED1B115A6CC0, _binary 0xB8BB82F331E2406A96EDF99EBAF9947A, 'dummy', '2020-01-27 14:27:40', '2020-01-27 14:27:40', _binary 0x547b6fefc3a440b4bc99944f6340c907, 'off'),
(_binary 0x7C14E872E00A432E8B72AD5679522CD4, _binary 0xB8BB82F331E2406A96EDF99EBAF9947A, 'dummy', '2020-01-27 14:27:40', '2020-01-27 14:27:40', _binary 0x547b6fefc3a440b4bc99944f6340c907, 'off'),
(_binary 0x827D61F75DCF4CAB9662F386F6FB0BCE, _binary 0xC64BA1C40EDA4CAB87A04D634F7B67F4, 'dummy', '2020-01-27 14:28:17', '2020-01-27 14:28:17', _binary 0x547b6fefc3a440b4bc99944f6340c907, 'on'),
(_binary 0xC40E6E574FE043B088ED4F0374E8623D, _binary 0x1B17BCAAA19E45F098B456211CC648AE, 'dummy', '2020-01-27 14:24:34', '2020-01-27 14:24:34', _binary 0x5c28d04c1bae4f7c8bae370c0283ee68, 'off'),
(_binary 0xCFCA08FFD19948ED9F008C6B840A567A, _binary 0x0B48DFBCFAC2429288DC7981A121602D, 'dummy', '2020-01-27 20:49:53', '2020-01-27 20:49:53', _binary 0x5c28d04c1bae4f7c8bae370c0283ee68, 'on'),
(_binary 0xD062CE8B95434B9BB6CA51907EC0246A, _binary 0xC64BA1C40EDA4CAB87A04D634F7B67F4, 'dummy', '2020-01-27 14:28:17', '2020-01-27 14:28:17', _binary 0x5c28d04c1bae4f7c8bae370c0283ee68, 'on'),
(_binary 0xE7496BD77BD64BD89ABB013261B88543, _binary 0x421CA8E926C6463089BAC53AEA9BCB1E, 'dummy', '2020-01-27 14:25:54', '2020-01-27 14:25:54', _binary 0x28989c89e7d746649d18a73647a844fb, 'on'),
(_binary 0xEA072FFF125E43B09D764A65738F4B88, _binary 0x1B17BCAAA19E45F098B456211CC648AE, 'dummy', '2020-01-27 14:24:34', '2020-01-27 14:24:34', _binary 0x28989c89e7d746649d18a73647a844fb, 'on');

INSERT IGNORE INTO `fb_triggers_module_notifications` (`notification_id`, `trigger_id`, `created_at`, `updated_at`, `notification_type`, `notification_email`, `notification_phone`) VALUES
(_binary 0x05F28DF95F194923B3F8B9090116DADC, _binary 0xC64BA1C40EDA4CAB87A04D634F7B67F4, '2020-04-06 13:16:17', '2020-04-06 13:16:17', 'email', 'john.doe@fastybird.com', NULL),
(_binary 0x4FE1019CF49E4CBF83E620B394E76317, _binary 0xC64BA1C40EDA4CAB87A04D634F7B67F4, '2020-04-06 13:27:07', '2020-04-06 13:27:07', 'sms', NULL, '+420778776776');

INSERT
IGNORE INTO `fb_security_policies` (`policy_id`, `policy_type`, `policy_v0`, `policy_v1`, `policy_v2`, `policy_v3`, `policy_v4`, `policy_v5`, `policy_policy_type`) VALUES
(_binary 0xb12082a40f5f4f3fb4e8c0d0ca209613, 'p', 'administrator', null, null, null, null, null, 'policy'),
(_binary 0xf5fc546183c94344ab43824619faaa91, 'p', 'manager', null, null, null, null, null, 'policy'),
(_binary 0x110e9e3b0d724827974906f89c368bc1, 'p', 'user', null, null, null, null, null, 'policy'),
(_binary 0x46f0644f91eb4877a3b032ab29189794, 'p', 'visitor', null, null, null, null, null, 'policy'),
(_binary 0x253fcd3a59b847288d6d017b02bc10e2, 'g', '5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34', 'administrator', null, null, null, null, 'policy'),
(_binary 0x15592686e28a4208bc1dc7134ff472cc, 'g', 'efbfbdef-bfbd-68ef-bfbd-770b40efbfbd', 'user', null, null, null, null, 'policy');
