<?php declare(strict_types = 1);

use FastyBird\FbMqttConnector\Exceptions;

return [
	'attr-unknown' => [
		'/fb/v1/device-name/$unknown',
		Exceptions\ParseMessage::class,
		'Provided topic is not valid',
	],
];
