<?php declare(strict_types = 1);

use FastyBird\FbMqttConnector\Exceptions;

return [
	'fw-not-valid' => [
		'/fb/v1/device-name/$fw/not-valid',
		Exceptions\ParseMessage::class,
		'Provided topic is not valid',
	],
];
