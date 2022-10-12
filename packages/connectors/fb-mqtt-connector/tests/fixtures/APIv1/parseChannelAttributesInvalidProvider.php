<?php declare(strict_types = 1);

use FastyBird\FbMqttConnector\Entities;
use FastyBird\FbMqttConnector\Exceptions;

return [
	'attr-' . Entities\Messages\Attribute::CHANNELS => [
		'/fb/v1/device-name/$channel/channel-name/$' . Entities\Messages\Attribute::CHANNELS,
		Exceptions\ParseMessage::class,
		'Provided topic is not valid',
	],
	'attr-other' => [
		'/fb/v1/device-name/$channel/channel-name/$other',
		Exceptions\ParseMessage::class,
		'Provided topic is not valid',
	],
];
