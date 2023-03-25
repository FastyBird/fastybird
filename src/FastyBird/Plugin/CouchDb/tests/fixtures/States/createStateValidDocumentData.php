<?php declare(strict_types = 1);

use FastyBird\CouchDbPlugin\States;
use Ramsey\Uuid\Uuid;

return [
	'one' => [
		States\State::class,
		[
			'id' => Uuid::uuid4()->toString(),
		],
	],
	'two' => [
		States\State::class,
		[
			'id' => Uuid::uuid4()->toString(),
		],
	],
];
