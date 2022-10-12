<?php declare(strict_types = 1);

use FastyBird\Metadata\Entities;

return [
	'get' => [
		file_get_contents(__DIR__ . '/data/channel.property.get.json'),
		Entities\Actions\ActionChannelProperty::class,
	],
	'set' => [
		file_get_contents(__DIR__ . '/data/channel.property.set.json'),
		Entities\Actions\ActionChannelProperty::class,
	],
];
