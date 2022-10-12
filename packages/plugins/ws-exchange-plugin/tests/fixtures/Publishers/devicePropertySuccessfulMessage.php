<?php declare(strict_types = 1);

use FastyBird\Metadata;
use Nette\Utils;

return [
	'create' => [
		Utils\ArrayHash::from([
			'id' => 'b41efd22-42e0-4e30-aab4-de471741cd30',
			'type' => Metadata\Types\PropertyType::TYPE_DYNAMIC,
			'device' => '6a6a8525-eb24-4f48-8698-4f446b4a2664',
			'property' => '667e159f-ad74-4ba7-94d7-775b1930ea11',
			'identifier' => 'property-name',
			'name' => null,
			'settable' => true,
			'queryable' => true,
			'data_type' => Metadata\Types\DataType::DATA_TYPE_UNKNOWN,
			'unit' => null,
			'format' => null,
			'invalid' => null,
			'number_of_decimals' => null,
		]),
		Metadata\Types\RoutingKey::get(Metadata\Types\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_CREATED),
		Metadata\Types\ModuleSource::get(Metadata\Types\ModuleSource::SOURCE_MODULE_DEVICES),
	],
	'update' => [
		Utils\ArrayHash::from([
			'id' => 'b41efd22-42e0-4e30-aab4-de471741cd30',
			'type' => Metadata\Types\PropertyType::TYPE_DYNAMIC,
			'device' => '6a6a8525-eb24-4f48-8698-4f446b4a2664',
			'property' => '667e159f-ad74-4ba7-94d7-775b1930ea11',
			'identifier' => 'property-name',
			'name' => null,
			'settable' => true,
			'queryable' => true,
			'data_type' => Metadata\Types\DataType::DATA_TYPE_UNKNOWN,
			'unit' => null,
			'format' => null,
			'invalid' => null,
			'number_of_decimals' => null,
		]),
		Metadata\Types\RoutingKey::get(Metadata\Types\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_UPDATED),
		Metadata\Types\ModuleSource::get(Metadata\Types\ModuleSource::SOURCE_MODULE_DEVICES),
	],
	'delete' => [
		Utils\ArrayHash::from([
			'id' => 'b41efd22-42e0-4e30-aab4-de471741cd30',
			'type' => Metadata\Types\PropertyType::TYPE_DYNAMIC,
			'device' => '6a6a8525-eb24-4f48-8698-4f446b4a2664',
			'property' => '667e159f-ad74-4ba7-94d7-775b1930ea11',
			'identifier' => 'property-name',
			'name' => null,
			'settable' => true,
			'queryable' => true,
			'data_type' => Metadata\Types\DataType::DATA_TYPE_UNKNOWN,
			'unit' => null,
			'format' => null,
			'invalid' => null,
			'number_of_decimals' => null,
		]),
		Metadata\Types\RoutingKey::get(Metadata\Types\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_DELETED),
		Metadata\Types\ModuleSource::get(Metadata\Types\ModuleSource::SOURCE_MODULE_DEVICES),
	],
];
