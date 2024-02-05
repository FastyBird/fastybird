<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Tests\Fixtures\Dummy;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

final class DummyDeviceSchema extends DevicesSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT . '/device/' . DummyDeviceEntity::TYPE;

	public function getEntityClass(): string
	{
		return DummyDeviceEntity::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
