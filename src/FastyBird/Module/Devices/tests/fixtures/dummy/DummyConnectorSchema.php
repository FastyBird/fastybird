<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Fixtures\Dummy;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas;

final class DummyConnectorSchema extends Schemas\Connectors\Connector
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Module::DEVICES . '/connector/dummy';

	public function getEntityClass(): string
	{
		return DummyConnectorEntity::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
