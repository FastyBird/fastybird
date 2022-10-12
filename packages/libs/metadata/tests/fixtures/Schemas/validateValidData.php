<?php declare(strict_types = 1);

use Nette\Utils;

return [
	'one' => [
		Utils\Json::encode([
			'attributeOne' => 'String value',
			'attributeTwo' => 20,
			'attributeThree' => false,
			'attributeFour' => null,
		]),
		file_get_contents(__DIR__ . '/validator.schema.json'),
		[
			'attributeOne' => 'String value',
			'attributeTwo' => 20,
			'attributeThree' => false,
			'attributeFour' => null,
		],
	],
	'two' => [
		Utils\Json::encode([
			'attributeOne' => 'String value',
			'attributeTwo' => 20,
		]),
		file_get_contents(__DIR__ . '/validator.schema.json'),
		[
			'attributeOne' => 'String value',
			'attributeTwo' => 20,
			'attributeThree' => true,
			'attributeFour' => null,
		],
	],
	'three' => [
		Utils\Json::encode([
			'attributeOne' => 'String value',
			'attributeTwo' => 2.2,
			'attributeThree' => false,
			'attributeFour' => 'String content',
		]),
		file_get_contents(__DIR__ . '/validator.schema.json'),
		[
			'attributeOne' => 'String value',
			'attributeTwo' => 2.2,
			'attributeThree' => false,
			'attributeFour' => 'String content',
		],
	],
];
