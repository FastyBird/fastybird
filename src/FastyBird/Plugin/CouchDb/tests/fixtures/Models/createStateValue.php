<?php declare(strict_types = 1);

use Ramsey\Uuid;

$id = Uuid\Uuid::uuid4();

return [
	'one' => [
		[
			'value' => 'new',
		],
		[
			'value' => 'new',
		],
		[
			'value' => 'new',
			'created' => null,
			'updated' => null,
		],
	],
	'two' => [
		[
			'id' => $id->toString(),
			'value' => null,
		],
		[
			'value' => null,
		],
		[
			'id' => $id->toString(),
			'value' => null,
			'created' => null,
			'updated' => null,
		],
	],
];
