<?php declare(strict_types = 1);

use FastyBird\FbMqttConnector\Entities;

return [
	'fw-' . Entities\Messages\ExtensionAttribute::MANUFACTURER => [
		'/fb/v1/device-name/$fw/' . Entities\Messages\ExtensionAttribute::MANUFACTURER,
		'value-content',
		[
			'device' => 'device-name',
			'retained' => false,
			Entities\Messages\ExtensionAttribute::MANUFACTURER => 'value-content',
		],
	],
	'fw-' . Entities\Messages\ExtensionAttribute::VERSION => [
		'/fb/v1/device-name/$fw/' . Entities\Messages\ExtensionAttribute::VERSION,
		'value-content',
		[
			'device' => 'device-name',
			'retained' => false,
			Entities\Messages\ExtensionAttribute::VERSION => 'value-content',
		],
	],
];
