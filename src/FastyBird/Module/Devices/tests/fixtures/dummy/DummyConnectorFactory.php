<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Fixtures\Dummy;

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Module\Devices\Connectors;
use FastyBird\Module\Devices\Connectors\Connector;

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
