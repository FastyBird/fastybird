<?php declare(strict_types = 1);

use FastyBird\FbMqttConnector\Entities;

return [
	'attr-' . Entities\Messages\Attribute::NAME => [
		'/fb/v1/device-name/$channel/channel-name/$' . Entities\Messages\Attribute::NAME,
		'Some content',
		[
			'device' => 'device-name',
			'channel' => 'channel-name',
			'retained' => false,
			Entities\Messages\Attribute::NAME => 'Some content',
		],
	],
	'attr-' . Entities\Messages\Attribute::PROPERTIES => [
		'/fb/v1/device-name/$channel/channel-name/$' . Entities\Messages\Attribute::PROPERTIES,
		'prop1,prop2',
		[
			'device' => 'device-name',
			'channel' => 'channel-name',
			'retained' => false,
			Entities\Messages\Attribute::PROPERTIES => ['prop1', 'prop2'],
		],
	],
	'attr-' . Entities\Messages\Attribute::CONTROLS => [
		'/fb/v1/device-name/$channel/channel-name/$' . Entities\Messages\Attribute::CONTROLS,
		'configure,reset',
		[
			'device' => 'device-name',
			'channel' => 'channel-name',
			'retained' => false,
			Entities\Messages\Attribute::CONTROLS => ['configure', 'reset'],
		],
	],
];
