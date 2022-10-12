<?php declare(strict_types = 1);

use FastyBird\FbMqttConnector\Entities;

return [
	'hw-' . Entities\Messages\ExtensionAttribute::MAC_ADDRESS => [
		'/fb/v1/device-name/$hw/' . Entities\Messages\ExtensionAttribute::MAC_ADDRESS,
		'00:0a:95:9d:68:16',
		[
			'device' => 'device-name',
			'retained' => false,
			Entities\Messages\ExtensionAttribute::MAC_ADDRESS => '000a959d6816',
		],
	],
	'hw-' . Entities\Messages\ExtensionAttribute::MANUFACTURER => [
		'/fb/v1/device-name/$hw/' . Entities\Messages\ExtensionAttribute::MANUFACTURER,
		'value-content',
		[
			'device' => 'device-name',
			'retained' => false,
			Entities\Messages\ExtensionAttribute::MANUFACTURER => 'value-content',
		],
	],
	'hw-' . Entities\Messages\ExtensionAttribute::MODEL => [
		'/fb/v1/device-name/$hw/' . Entities\Messages\ExtensionAttribute::MODEL,
		'value-content',
		[
			'device' => 'device-name',
			'retained' => false,
			Entities\Messages\ExtensionAttribute::MODEL => 'value-content',
		],
	],
	'hw-' . Entities\Messages\ExtensionAttribute::VERSION => [
		'/fb/v1/device-name/$hw/' . Entities\Messages\ExtensionAttribute::VERSION,
		'value-content',
		[
			'device' => 'device-name',
			'retained' => false,
			Entities\Messages\ExtensionAttribute::VERSION => 'value-content',
		],
	],
];
