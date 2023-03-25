<?php declare(strict_types = 1);

use Ramsey\Uuid;

$id = Uuid\Uuid::uuid4()->toString();
$now = new DateTimeImmutable();

return [
	'one' => [
		[
			'updated' => $now->format(DATE_ATOM),
		],
		[
			'id' => $id,
			'value' => 'value',
			'updated' => null,
		],
		[
			'id' => $id,
			'value' => 'value',
			'created' => null,
			'updated' => $now->format(DATE_ATOM),
		],
	],
	'two' => [
		[
			'updated' => $now->format(DATE_ATOM),
		],
		[
			'id' => $id,
			'value' => 'value',
			'created' => $now->format(DATE_ATOM),
			'updated' => null,
		],
		[
			'id' => $id,
			'value' => 'value',
			'created' => $now->format(DATE_ATOM),
			'updated' => $now->format(DATE_ATOM),
		],
	],
];
