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

INSERT IGNORE INTO `fb_triggers_module_conditions` (`condition_id`, `trigger_id`, `created_at`, `updated_at`, `condition_type`, `condition_device`, `condition_channel`, `condition_channel_property`, `condition_operator`, `condition_operand`) VALUES
(_binary 0x2726F19C7759440EB6F58C3306692FA2, _binary 0x2CEA2C1B47904D828A9F902C7155AB36, '2020-01-27 14:25:19', '2020-01-27 14:25:19', 'channel-property', _binary 0x28989c89e7d746649d18a73647a844fb,_binary 0x5421c2688f5d4972a7b56b4295c3e4b1, _binary 0xff7b36d7a0b043369efba608c93b0974, 'eq', '3');

INSERT IGNORE INTO `fb_triggers_module_actions` (`action_id`, `trigger_id`, `action_type`, `created_at`, `updated_at`, `action_device`, `action_channel`, `action_channel_property`, `action_value`) VALUES
(_binary 0x21D13F148BE0462587644D5B1F3B4D1E, _binary 0x0B48DFBCFAC2429288DC7981A121602D, 'channel-property', '2020-01-28 18:39:35', '2020-01-28 18:39:35', _binary 0xa830828c67684274b90920ce0e222347, _binary 0x89edfcb22ee4427dbb63d629b119ede1, _binary 0x408c20d8961444499392ee03ce43a4b6, 'on'),
(_binary 0x46C39A9539EB42169FA34D575A6295BD, _binary 0x421CA8E926C6463089BAC53AEA9BCB1E, 'channel-property', '2020-01-27 14:25:54', '2020-01-27 14:25:54', _binary 0xa830828c67684274b90920ce0e222347, _binary 0x29f6d2fd70154fb8b366d78a5d3808cc, _binary 0x00a4c415ebf047b88012234fe30e74f5, 'on'),
(_binary 0x4AA84028D8B7412895B2295763634AA4, _binary 0xC64BA1C40EDA4CAB87A04D634F7B67F4, 'channel-property', '2020-01-27 14:28:17', '2020-01-27 14:28:17', _binary 0xa830828c67684274b90920ce0e222347, _binary 0x4f692f945be6438494a760c424a5f723, _binary 0x7bc1fc818ace409db044810140e2361a, 'on'),
(_binary 0x52AA8A3518324317BE2C8B8FFFAAE07F, _binary 0xB8BB82F331E2406A96EDF99EBAF9947A, 'channel-property', '2020-01-29 16:43:32', '2020-01-29 16:43:32', _binary 0xa830828c67684274b90920ce0e222347, _binary 0x97f9d29a4faf4861895ed2dba9b35a08, _binary 0xcd1fc3217b804698af5bd247bf5cca78, 'on'),
(_binary 0x69CED64E6E5441E98052BA25E6199B25, _binary 0x2CEA2C1B47904D828A9F902C7155AB36, 'channel-property', '2020-01-27 14:25:19', '2020-01-27 14:25:19', _binary 0x547b6fefc3a440b4bc99944f6340c907, _binary 0xfda320444a6a4278853ca5bd907e3d9d, _binary 0x5a3de05b483247dd976ce015e8675af7, 'off'),
(_binary 0x7B6398E4D26C4CB1BA0CED1B115A6CC0, _binary 0xB8BB82F331E2406A96EDF99EBAF9947A, 'channel-property', '2020-01-27 14:27:40', '2020-01-27 14:27:40', _binary 0x547b6fefc3a440b4bc99944f6340c907, _binary 0x29b6c47f328648e3a8db22e6e18a0792, _binary 0xaa1da84685bb43f2b4e5fc267a7dc3bc, 'off'),
(_binary 0x7C14E872E00A432E8B72AD5679522CD4, _binary 0xB8BB82F331E2406A96EDF99EBAF9947A, 'channel-property', '2020-01-27 14:27:40', '2020-01-27 14:27:40', _binary 0x547b6fefc3a440b4bc99944f6340c907, _binary 0x20f11357b2e94381886e0dcdf789cf4e, _binary 0xc104d793ff6c4758b97483e4b50478af, 'off'),
(_binary 0x827D61F75DCF4CAB9662F386F6FB0BCE, _binary 0xC64BA1C40EDA4CAB87A04D634F7B67F4, 'channel-property', '2020-01-27 14:28:17', '2020-01-27 14:28:17', _binary 0x547b6fefc3a440b4bc99944f6340c907, _binary 0xe177502330fb4777a14ccb779c7fda63, _binary 0xffe411055fd14e16b9b3ff51fa4d0c30, 'on'),
(_binary 0xC40E6E574FE043B088ED4F0374E8623D, _binary 0x1B17BCAAA19E45F098B456211CC648AE, 'channel-property', '2020-01-27 14:24:34', '2020-01-27 14:24:34', _binary 0x5c28d04c1bae4f7c8bae370c0283ee68, _binary 0xdb2baa1cd64f447d973dfd0f6173cd95, _binary 0x0e0040860a424221b1a1a5029664c638, 'off'),
(_binary 0xCFCA08FFD19948ED9F008C6B840A567A, _binary 0x0B48DFBCFAC2429288DC7981A121602D, 'channel-property', '2020-01-27 20:49:53', '2020-01-27 20:49:53', _binary 0x5c28d04c1bae4f7c8bae370c0283ee68, _binary 0x39c26c3f8ef8485ca7e0e57499ea1229, _binary 0xfe263550c82746f8bbb6ef53a3b20e08, 'on'),
(_binary 0xD062CE8B95434B9BB6CA51907EC0246A, _binary 0xC64BA1C40EDA4CAB87A04D634F7B67F4, 'channel-property', '2020-01-27 14:28:17', '2020-01-27 14:28:17', _binary 0x5c28d04c1bae4f7c8bae370c0283ee68, _binary 0x51d05010c079494cba739ea206f6bad1, _binary 0x4227fccf28af4080a0d9d329ecc7350d, 'on'),
(_binary 0xE7496BD77BD64BD89ABB013261B88543, _binary 0x421CA8E926C6463089BAC53AEA9BCB1E, 'channel-property', '2020-01-27 14:25:54', '2020-01-27 14:25:54', _binary 0x28989c89e7d746649d18a73647a844fb, _binary 0x90b9e21ca77f409cbd944b63ee85d078, _binary 0xbb7accd6093e454c93631a9f2cfffff3, 'on'),
(_binary 0xEA072FFF125E43B09D764A65738F4B88, _binary 0x1B17BCAAA19E45F098B456211CC648AE, 'channel-property', '2020-01-27 14:24:34', '2020-01-27 14:24:34', _binary 0x28989c89e7d746649d18a73647a844fb, _binary 0x5421c2688f5d4972a7b56b4295c3e4b1, _binary 0xdb5e2230ac1446fd959281a1f1e28380, 'on');

INSERT IGNORE INTO `fb_triggers_module_notifications` (`notification_id`, `trigger_id`, `created_at`, `updated_at`, `notification_type`, `notification_email`, `notification_phone`) VALUES
(_binary 0x05F28DF95F194923B3F8B9090116DADC, _binary 0xC64BA1C40EDA4CAB87A04D634F7B67F4, '2020-04-06 13:16:17', '2020-04-06 13:16:17', 'email', 'john.doe@fastybird.com', NULL),
(_binary 0x4FE1019CF49E4CBF83E620B394E76317, _binary 0xC64BA1C40EDA4CAB87A04D634F7B67F4, '2020-04-06 13:27:07', '2020-04-06 13:27:07', 'sms', NULL, '+420778776776');
