<?php declare(strict_types = 1);

namespace FastyBird\DevicesModule\Tests\Fixtures\Dummy;

use FastyBird\DevicesModule\Connectors;
use FastyBird\DevicesModule\Connectors\Connector;
use FastyBird\Library\Metadata\Entities as MetadataEntities;

class DummyConnectorFactory implements Connectors\ConnectorFactory
{

	public function getType(): string
	{
		return 'dummy';
	}

	public function create(MetadataEntities\DevicesModule\Connector $connector): Connector
	{
		return new DummyConnector();
	}

}
