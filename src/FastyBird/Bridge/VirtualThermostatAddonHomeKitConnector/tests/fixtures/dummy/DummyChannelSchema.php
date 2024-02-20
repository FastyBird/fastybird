<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Tests\Fixtures\Dummy;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

final class DummyChannelSchema extends DevicesSchemas\Channels\Channel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Connector::VIRTUAL->value . '/device/' . DummyChannelEntity::TYPE;

	public function getEntityClass(): string
	{
		return DummyChannelEntity::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
