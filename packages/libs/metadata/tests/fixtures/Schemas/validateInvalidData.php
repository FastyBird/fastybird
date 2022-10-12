<?php declare(strict_types = 1);

use Nette\Utils;

return [
	'one' => [
		Utils\Json::encode([
			'attributeOne' => 13,
			'attributeTwo' => 20,
			'attributeThree' => false,
			'attributeFour' => null,
		]),
		file_get_contents(__DIR__ . '/validator.schema.json'),
	],
	'two' => [
		Utils\Json::encode([
			'attributeOne' => 'String value',
			'attributeTwo' => 'String value',
		]),
		file_get_contents(__DIR__ . '/validator.schema.json'),
	],
	'three' => [
		Utils\Json::encode([
			'attributeOne' => 'String value',
			'attributeTwo' => 2.2,
			'attributeThree' => 10,
			'attributeFour' => 'String content',
		]),
		file_get_contents(__DIR__ . '/validator.schema.json'),
	],
];
