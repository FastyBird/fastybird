<?php declare(strict_types = 1);

use FastyBird\Metadata;
use Nette\Utils;

return [
	'create' => [
		Utils\ArrayHash::from([
			'id' => 'd627e987-a9aa-4f23-90c3-4fc38ae81ee1',
			'device' => '6a6a8525-eb24-4f48-8698-4f446b4a2664',
			'channel' => '740a1615-8a61-46ed-8d72-192ed20c7aed',
			'identifier' => 'channel-name',
			'name' => 'Channel custom name',
			'title' => null,
			'comment' => null,
		]),
		Metadata\Types\RoutingKey::get(Metadata\Types\RoutingKey::ROUTE_CHANNEL_ENTITY_CREATED),
		Metadata\Types\ModuleSource::get(Metadata\Types\ModuleSource::SOURCE_MODULE_DEVICES),
	],
	'update' => [
		Utils\ArrayHash::from([
			'id' => 'd627e987-a9aa-4f23-90c3-4fc38ae81ee1',
			'device' => '6a6a8525-eb24-4f48-8698-4f446b4a2664',
			'channel' => '740a1615-8a61-46ed-8d72-192ed20c7aed',
			'identifier' => 'channel-name',
			'name' => 'Channel custom name',
			'title' => null,
			'comment' => null,
		]),
		Metadata\Types\RoutingKey::get(Metadata\Types\RoutingKey::ROUTE_CHANNEL_ENTITY_UPDATED),
		Metadata\Types\ModuleSource::get(Metadata\Types\ModuleSource::SOURCE_MODULE_DEVICES),
	],
	'delete' => [
		Utils\ArrayHash::from([
			'id' => 'd627e987-a9aa-4f23-90c3-4fc38ae81ee1',
			'device' => '6a6a8525-eb24-4f48-8698-4f446b4a2664',
			'channel' => '740a1615-8a61-46ed-8d72-192ed20c7aed',
			'identifier' => 'channel-name',
			'name' => 'Channel custom name',
			'title' => null,
			'comment' => null,
		]),
		Metadata\Types\RoutingKey::get(Metadata\Types\RoutingKey::ROUTE_CHANNEL_ENTITY_DELETED),
		Metadata\Types\ModuleSource::get(Metadata\Types\ModuleSource::SOURCE_MODULE_DEVICES),
	],
];
