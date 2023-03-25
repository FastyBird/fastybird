<?php declare(strict_types = 1);

use FastyBird\CouchDbStoragePlugin\States;

return [
	'one' => [
		States\State::class,
		[],
	],
	'two' => [
		States\State::class,
		[
			'id' => 'invalid-string',
		],
	],
];
