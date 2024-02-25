<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Tests\Fixtures\Dummy;

use FastyBird\Library\Metadata\Documents\Mapping as DOC;
use FastyBird\Module\Devices\Documents;

#[DOC\Document(entity: DummyConnectorEntity::class)]
#[DOC\DiscriminatorEntry(name: DummyConnectorEntity::TYPE)]
class DummyConnectorDocument extends Documents\Connectors\Connector
{

	public static function getType(): string
	{
		return DummyConnectorEntity::TYPE;
	}

}
