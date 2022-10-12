<?php declare(strict_types = 1);

use FastyBird\Metadata;
use Nette\Utils;

return [
	'create' => [
		Utils\ArrayHash::from([
			'id' => '73b106f6-bbd4-4bed-b6ac-bc4e8cab9e52',
			'type' => Metadata\Types\PropertyType::TYPE_DYNAMIC,
			'device' => '6a6a8525-eb24-4f48-8698-4f446b4a2664',
			'channel' => '740a1615-8a61-46ed-8d72-192ed20c7aed',
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
		Metadata\Types\RoutingKey::get(Metadata\Types\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_CREATED),
		Metadata\Types\ModuleSource::get(Metadata\Types\ModuleSource::SOURCE_MODULE_DEVICES),
	],
	'update' => [
		Utils\ArrayHash::from([
			'id' => '73b106f6-bbd4-4bed-b6ac-bc4e8cab9e52',
			'type' => Metadata\Types\PropertyType::TYPE_DYNAMIC,
			'device' => '6a6a8525-eb24-4f48-8698-4f446b4a2664',
			'channel' => '740a1615-8a61-46ed-8d72-192ed20c7aed',
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
		Metadata\Types\RoutingKey::get(Metadata\Types\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_UPDATED),
		Metadata\Types\ModuleSource::get(Metadata\Types\ModuleSource::SOURCE_MODULE_DEVICES),
	],
	'delete' => [
		Utils\ArrayHash::from([
			'id' => '73b106f6-bbd4-4bed-b6ac-bc4e8cab9e52',
			'type' => Metadata\Types\PropertyType::TYPE_DYNAMIC,
			'device' => '6a6a8525-eb24-4f48-8698-4f446b4a2664',
			'channel' => '740a1615-8a61-46ed-8d72-192ed20c7aed',
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
		Metadata\Types\RoutingKey::get(Metadata\Types\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_DELETED),
		Metadata\Types\ModuleSource::get(Metadata\Types\ModuleSource::SOURCE_MODULE_DEVICES),
	],
];
