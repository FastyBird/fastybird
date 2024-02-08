INSERT
IGNORE INTO `fb_accounts_module_accounts` (`account_id`, `account_state`, `account_last_visit`, `params`, `created_at`, `updated_at`, `account_request_hash`)
VALUES (_binary 0x5E79EFBFBD0D5B7C46EFBFBDEFBFBD34, 'active', '2019-11-07 22:30:56', '{"datetime": {"zone": "Europe/Prague", "format": {"date": "DD.MM.YYYY", "time": "HH:mm"}, "week_start": 1}}', '2017-01-03 11:30:00', '2017-01-03 11:30:00', 'NGZqMmVxdnhubjJpIyMxNTc0NDUwNDAz'),
       (_binary 0xEFBFBDEFBFBD68EFBFBD770B40EFBFBD, 'active', '2019-05-29 07:38:24', '{"datetime": {"zone": "Europe/Prague", "format": {"date": "DD.MM.YYYY", "time": "HH:mm"}, "week_start": 1}}', '2017-01-04 12:30:00', '2017-01-04 12:30:00', 'YjRqZXFoZGw1Z3ZzIyMxNTc0MjA3NDQ1'),
       (_binary 0xFAE8D7817E2C43189C8543BA637D14C5, 'approval_waiting', '2019-05-29 07:38:24', '{"datetime": {"zone": "Europe/Prague", "format": {"date": "DD.MM.YYYY", "time": "HH:mm"}, "week_start": 1}}', '2017-01-04 12:30:00', '2017-01-04 12:30:00', 'YjRqZXFoZGw1Z3ZzIyMxNTc0MjA3NDQ1'),
       (_binary 0xFD23CCB48D874EB394DA638AB4E10AE3, 'not_activated', '2019-05-29 07:38:24', '{"datetime": {"zone": "Europe/Prague", "format": {"date": "DD.MM.YYYY", "time": "HH:mm"}, "week_start": 1}}', '2017-01-04 12:30:00', '2017-01-04 12:30:00', 'YjRqZXFoZGw1Z3ZzIyMxNTc0MjA3NDQ1'),
       (_binary 0xFDBE2CE23B1841F1AAABC3C56D286EB4, 'deleted', '2019-05-29 07:38:24', '{"datetime": {"zone": "Europe/Prague", "format": {"date": "DD.MM.YYYY", "time": "HH:mm"}, "week_start": 1}}', '2017-01-04 12:30:00', '2017-01-04 12:30:00', 'YjRqZXFoZGw1Z3ZzIyMxNTc0MjA3NDQ1'),
       (_binary 0xFE1152868CFD41BFACEB6CA95BAF6FE9, 'blocked', '2019-05-29 07:38:24', '{"datetime": {"zone": "Europe/Prague", "format": {"date": "DD.MM.YYYY", "time": "HH:mm"}, "week_start": 1}}', '2017-01-04 12:30:00', '2017-01-04 12:30:00', 'YjRqZXFoZGw1Z3ZzIyMxNTc0MjA3NDQ1');

INSERT
IGNORE INTO `fb_accounts_module_accounts_details` (`detail_id`, `account_id`, `detail_first_name`, `detail_last_name`, `detail_middle_name`, `created_at`, `updated_at`)
VALUES (_binary 0xEFBFBDCFAA74EFBFBD4CEFBFBDEFBFBD, _binary 0x5E79EFBFBD0D5B7C46EFBFBDEFBFBD34, 'John', 'Doe', NULL, '2017-01-03 11:30:00', '2017-01-03 11:30:00'),
       (_binary 0xEFBFBDEFBFBDEFBFBD4011EFBFBD4254, _binary 0xEFBFBDEFBFBD68EFBFBD770B40EFBFBD, 'Jane', 'Doe', NULL, '2017-01-04 12:30:00', '2017-01-04 12:30:00'),
       (_binary 0xF3CCE15AF9564C7EA4B3AC31A0017AC9, _binary 0xFDBE2CE23B1841F1AAABC3C56D286EB4, 'Peter', 'Parker', 'Deleted', '2017-01-04 12:30:00', '2017-01-04 12:30:00'),
       (_binary 0xF48FC1C96CAD483E8F847EB001378DC9, _binary 0xFE1152868CFD41BFACEB6CA95BAF6FE9, 'Peter', 'Pan', 'Blocked', '2017-01-04 12:30:00', '2017-01-04 12:30:00'),
       (_binary 0xF599AF7982F24C7B8F9B1594F1EE5E8A, _binary 0xFD23CCB48D874EB394DA638AB4E10AE3, 'Bruce', 'Wane', 'Unactivated', '2017-01-04 12:30:00', '2017-01-04 12:30:00'),
       (_binary 0xF62132F799BE4ED4AD23190A883AF788, _binary 0xFAE8D7817E2C43189C8543BA637D14C5, 'Clark', 'Kent', 'Waiting', '2017-01-04 12:30:00', '2017-01-04 12:30:00');

INSERT
IGNORE INTO `fb_accounts_module_emails` (`email_id`, `account_id`, `email_address`, `email_default`, `email_verified`, `email_verification_hash`, `email_verification_created`, `email_verification_completed`, `email_visibility`, `created_at`, `updated_at`)
VALUES (_binary 0x0B46D3D6C980494A8B40F19E6095E610, _binary 0x5E79EFBFBD0D5B7C46EFBFBDEFBFBD34, 'john.doe@fastybird.com', 1, 1, NULL, NULL, '2019-09-22 20:29:16', 'public', '2019-09-22 20:29:16', '2019-09-22 20:29:16'),
       (_binary 0x32EBE3C30238482EAB796B1D9EE2147C, _binary 0x5E79EFBFBD0D5B7C46EFBFBDEFBFBD34, 'john.doe@fastybird.ovh', 0, 1, NULL, NULL, '2017-09-07 18:24:35', 'public', '2017-09-07 18:24:35', '2018-02-07 17:38:20'),
       (_binary 0x73EFBFBDEFBFBD3644EFBFBDEFBFBD7A, _binary 0xEFBFBDEFBFBD68EFBFBD770B40EFBFBD, 'jane.doe@fastybird.com', 1, 1, NULL, NULL, '2017-01-25 22:31:19', 'public', '2017-01-25 22:31:19', '2019-04-21 19:49:32'),
       (_binary 0x7D60DD96EFBFBD534E2BEFBFBDEFBFBD, _binary 0xFE1152868CFD41BFACEB6CA95BAF6FE9, 'peter.pan@fastybird.com', 1, 1, NULL, NULL, '2017-05-03 11:27:34', 'public', '2017-05-03 11:27:34', '2018-02-07 17:38:20'),
       (_binary 0xEB54A59E5C7A41E5BFAC4FB6EC4D6AD9, _binary 0xFDBE2CE23B1841F1AAABC3C56D286EB4, 'peter.parker@fastybird.com', 1, 1, NULL, NULL, '2017-05-03 11:27:34', 'public', '2017-05-03 11:27:34', '2018-02-07 17:38:20'),
       (_binary 0xBAEFEC20192741CEA7E0AE74BD641B9C, _binary 0xFD23CCB48D874EB394DA638AB4E10AE3, 'bruce.wane@fastybird.com', 1, 1, NULL, NULL, '2017-09-06 22:56:44', 'public', '2017-09-06 22:56:44', '2019-04-21 19:49:32'),
       (_binary 0xED987404F14C40B4915015B6590DEB8C, _binary 0xFAE8D7817E2C43189C8543BA637D14C5, 'clark.kent@fastybird.com', 1, 0, NULL, '2019-09-22 21:09:56', NULL, 'public', '2019-09-22 21:09:56', '2019-09-22 21:09:56');

INSERT
IGNORE INTO `fb_accounts_module_identities` (`identity_id`, `account_id`, `identity_uid`, `identity_state`, `created_at`, `updated_at`, `identity_token`, `params`)
VALUES (_binary 0x77331268EFBFBD3449EFBFBDEFBFBD04, _binary 0x5E79EFBFBD0D5B7C46EFBFBDEFBFBD34, 'john.doe@fastybird.com', 'active', '2017-05-03 11:27:34', '2019-05-29 07:14:00', '11b9174dbbc8f3f5ce3b3d270a3c73eedbe33832489aaa70fe0f370566bcd2f12a1a15041b318b57fd6ce6bb82471d9e2d7bc753064491bf418f9f9c3de21fcf', '{"salt": "89bwo"}'),
       (_binary 0xF2AB51A80B6F4ADBB51E1C648D41F24E, _binary 0xFDBE2CE23B1841F1AAABC3C56D286EB4, 'peter.parker@fastybird.com', 'active', '2017-01-25 12:42:26', '2019-12-10 20:19:01', '11b9174dbbc8f3f5ce3b3d270a3c73eedbe33832489aaa70fe0f370566bcd2f12a1a15041b318b57fd6ce6bb82471d9e2d7bc753064491bf418f9f9c3de21fcf', '{"salt": "89bwo"}'),
       (_binary 0xFAF7A863A49C4428A7571DE537773355, _binary 0xEFBFBDEFBFBD68EFBFBD770B40EFBFBD, 'jane.doe@fastybird.com', 'active', '2017-05-03 11:27:34', '2019-05-29 07:14:00', '11b9174dbbc8f3f5ce3b3d270a3c73eedbe33832489aaa70fe0f370566bcd2f12a1a15041b318b57fd6ce6bb82471d9e2d7bc753064491bf418f9f9c3de21fcf', '{"salt": "89bwo"}'),
       (_binary 0xFD3385A5D8D744FBB7F1151C62A4A7B4, _binary 0xFE1152868CFD41BFACEB6CA95BAF6FE9, 'peter.pan@fastybird.com', 'active', '2017-05-03 11:27:34', '2019-05-29 07:14:00', '11b9174dbbc8f3f5ce3b3d270a3c73eedbe33832489aaa70fe0f370566bcd2f12a1a15041b318b57fd6ce6bb82471d9e2d7bc753064491bf418f9f9c3de21fcf', '{"salt": "89bwo"}'),
       (_binary 0xFE1152868CFD41BFACEB6CA95BAF6FE9, _binary 0xFD23CCB48D874EB394DA638AB4E10AE3, 'bruce.wane@fastybird.com', 'active', '2017-05-03 11:27:34', '2019-05-29 07:14:00', '11b9174dbbc8f3f5ce3b3d270a3c73eedbe33832489aaa70fe0f370566bcd2f12a1a15041b318b57fd6ce6bb82471d9e2d7bc753064491bf418f9f9c3de21fcf', '{"salt": "89bwo"}'),
       (_binary 0xFF99F3C470B943F99EF6C9B33B43ABCB, _binary 0xFAE8D7817E2C43189C8543BA637D14C5, 'clark.kent@fastybird.com', 'active', '2017-05-03 11:27:34', '2019-05-29 07:14:00', '11b9174dbbc8f3f5ce3b3d270a3c73eedbe33832489aaa70fe0f370566bcd2f12a1a15041b318b57fd6ce6bb82471d9e2d7bc753064491bf418f9f9c3de21fcf', '{"salt": "89bwo"}');

INSERT
IGNORE INTO `fb_accounts_module_acl_roles` (`role_id`, `parent_id`, `role_name`, `role_comment`, `created_at`, `updated_at`)
VALUES (_binary 0xEFBFBD040158EFBFBDEFBFBD4DEFBFBD, NULL, 'guest', 'Guest', '2020-06-03 12:00:00', '2020-06-03 12:00:00'),
       (_binary 0xEFBFBDEFBFBDEFBFBD0FEFBFBD5C4F61, _binary 0xEFBFBD040158EFBFBDEFBFBD4DEFBFBD, 'user', 'User', '2020-06-03 12:00:00', '2020-06-03 12:00:00'),
       (_binary 0x89F4A14F7F78421699B8584AB9229F1C, _binary 0xEFBFBDEFBFBDEFBFBD0FEFBFBD5C4F61, 'manager', 'Manager', '2020-06-03 12:00:00', '2020-06-03 12:00:00'),
       (_binary 0x337A0518664B40EFBFBDEFBFBD7914EF, _binary 0x89F4A14F7F78421699B8584AB9229F1C, 'administrator', 'Administrator', '2020-06-03 12:00:00', '2020-06-03 12:00:00');

INSERT
IGNORE INTO `fb_accounts_module_accounts_roles` (`role_id`, `account_id`)
VALUES (_binary 0x337A0518664B40EFBFBDEFBFBD7914EF, _binary 0x5E79EFBFBD0D5B7C46EFBFBDEFBFBD34),
       (_binary 0xEFBFBDEFBFBDEFBFBD0FEFBFBD5C4F61, _binary 0xEFBFBDEFBFBD68EFBFBD770B40EFBFBD);

INSERT
IGNORE INTO `fb_security_tokens` (`token_id`, `parent_id`, `token_token`, `token_state`, `token_type`, `identity_id`, `token_valid_till`, `params`)
VALUES (_binary 0x4F6710A138AA42649C0CB45285181270, NULL, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJjb20uZmFzdHliaXJkLmF1dGgtbW9kdWxlIiwianRpIjoiMjQ3MTBlOTYtYTZmYi00ZmM3LWFhMzAtNDcyNzkwNWQzMDRjIiwiaWF0IjoxNTg1NzQyNDAwLCJleHAiOjE1ODU3NDk2MDAsInVzZXIiOiI1ZTc5ZWZiZi1iZDBkLTViN2MtNDZlZi1iZmJkZWZiZmJkMzQiLCJyb2xlcyI6WyJhZG1pbmlzdHJhdG9yIl19.QH_Oo_uzTXAb3pNnHvXYnnX447nfVq2_ggQ9ZxStu4s', 'active', 'access_token', _binary 0x77331268EFBFBD3449EFBFBDEFBFBD04, '2020-04-01 18:00:00', '[]'),
       (_binary 0x956A7E26E8C04BDB894ADD21B14E4A94, NULL, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJjb20uZmFzdHliaXJkLmF1dGgtbW9kdWxlIiwianRpIjoiYTVkMTliNDktNjI3Mi00ZGJkLWI3YWItNTJhY2QwMzY3MWE5IiwiaWF0IjoxNTg1NzQyNDAwLCJleHAiOjE1ODU3NDk2MDAsInVzZXIiOiJlZmJmYmRlZi1iZmJkLTY4ZWYtYmZiZC03NzBiNDBlZmJmYmQiLCJyb2xlcyI6WyJ1c2VyIl19.wi_KC5aDT-y6wKbA4wG29KPHUqFyEcNTI-TUvwIH5yc', 'active', 'access_token', _binary 0xFAF7A863A49C4428A7571DE537773355, '2020-04-01 18:00:00', '[]'),
       (_binary 0x35BE56240160432383EE6F59000934B4, _binary 0x4F6710A138AA42649C0CB45285181270, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1ODU3NDI0MDAsImV4cCI6MTU4NjAwMTYwMCwianRpIjoiMzViZTU2MjQtMDE2MC00MzIzLTgzZWUtNmY1OTAwMDkzNGI0Iiwic3ViIjoiNWU3OWVmYmYtYmQwZC01YjdjLTQ2ZWYtYmZiZGVmYmZiZDM0IiwidHlwZSI6InJlZnJlc2gifQ.JFlQH71H4FzdO8stTC8AuMNq1YDoCgXY7Ni0pyNX7NY', 'active', 'refresh_token', null, '2020-04-04 12:00:00', '[]'),
       (_binary 0x850E0E2716294136B18DD4628557A724, _binary 0x956A7E26E8C04BDB894ADD21B14E4A94, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1ODU3NDI0MDAsImV4cCI6MTU4NjAwMTYwMCwianRpIjoiODUwZTBlMjctMTYyOS00MTM2LWIxOGQtZDQ2Mjg1NTdhNzI0Iiwic3ViIjoiZWZiZmJkZWYtYmZiZC02OGVmLWJmYmQtNzcwYjQwZWZiZmJkIiwidHlwZSI6InJlZnJlc2gifQ.G-nrGqBdMFWivJOqpdVnjI-MxlJ-Jo8f-BVDesUVNZk', 'active', 'refresh_token', null, '2020-04-04 12:00:00', '[]');
