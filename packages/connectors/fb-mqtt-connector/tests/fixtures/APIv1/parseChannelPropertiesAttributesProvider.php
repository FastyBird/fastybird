<?php declare(strict_types = 1);

use FastyBird\FbMqttConnector\Entities;

return [
	'attr-' . Entities\Messages\PropertyAttribute::NAME => [
		'/fb/v1/device-name/$channel/channel-name/$property/some-property/$' . Entities\Messages\PropertyAttribute::NAME,
		'payload',
		[
			'device' => 'device-name',
			'channel' => 'channel-name',
			'retained' => false,
			'property' => 'some-property',
			Entities\Messages\PropertyAttribute::NAME => 'payload',
		],
	],
	'attr-' . Entities\Messages\PropertyAttribute::SETTABLE => [
		'/fb/v1/device-name/$channel/channel-name/$property/some-property/$' . Entities\Messages\PropertyAttribute::SETTABLE,
		'true',
		[
			'device' => 'device-name',
			'channel' => 'channel-name',
			'retained' => false,
			'property' => 'some-property',
			Entities\Messages\PropertyAttribute::SETTABLE => true,
		],
	],
	'attr-' . Entities\Messages\PropertyAttribute::QUERYABLE => [
		'/fb/v1/device-name/$channel/channel-name/$property/some-property/$' . Entities\Messages\PropertyAttribute::QUERYABLE,
		'invalid',
		[
			'device' => 'device-name',
			'channel' => 'channel-name',
			'retained' => false,
			'property' => 'some-property',
			Entities\Messages\PropertyAttribute::QUERYABLE => false,
		],
	],
];
