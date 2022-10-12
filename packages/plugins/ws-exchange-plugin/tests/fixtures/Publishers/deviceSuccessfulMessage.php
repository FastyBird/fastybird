<?php declare(strict_types = 1);

use FastyBird\Metadata;
use Nette\Utils;

return [
	'create' => [
		Utils\ArrayHash::from([
			'id' => '633c7f7c-f73b-456f-b65f-5359c3b23d9c',
			'type' => 'custom',
			'connector' => 'de9fbeaa-cc93-42a3-88db-23a393651ee4',
			'identifier' => 'device-name',
			'name' => 'Device name',
			'title' => null,
			'comment' => null,
			'state' => 'ready',
			'enabled' => true,
		]),
		Metadata\Types\RoutingKey::get(Metadata\Types\RoutingKey::ROUTE_DEVICE_ENTITY_CREATED),
		Metadata\Types\ModuleSource::get(Metadata\Types\ModuleSource::SOURCE_MODULE_DEVICES),
	],
	'update' => [
		Utils\ArrayHash::from([
			'id' => '633c7f7c-f73b-456f-b65f-5359c3b23d9c',
			'type' => 'custom',
			'connector' => 'de9fbeaa-cc93-42a3-88db-23a393651ee4',
			'identifier' => 'device-name',
			'name' => 'Device name',
			'title' => null,
			'comment' => null,
			'state' => 'ready',
			'enabled' => true,
		]),
		Metadata\Types\RoutingKey::get(Metadata\Types\RoutingKey::ROUTE_DEVICE_ENTITY_UPDATED),
		Metadata\Types\ModuleSource::get(Metadata\Types\ModuleSource::SOURCE_MODULE_DEVICES),
	],
	'delete' => [
		Utils\ArrayHash::from([
			'id' => '633c7f7c-f73b-456f-b65f-5359c3b23d9c',
			'type' => 'custom',
			'connector' => 'de9fbeaa-cc93-42a3-88db-23a393651ee4',
			'identifier' => 'device-name',
			'name' => 'Device name',
			'title' => null,
			'comment' => null,
			'state' => 'ready',
			'enabled' => true,
		]),
		Metadata\Types\RoutingKey::get(Metadata\Types\RoutingKey::ROUTE_DEVICE_ENTITY_DELETED),
		Metadata\Types\ModuleSource::get(Metadata\Types\ModuleSource::SOURCE_MODULE_DEVICES),
	],
];
