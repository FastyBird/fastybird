<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Tests\Fixtures\Dummy;

use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use FastyBird\Module\Devices\Entities as DevicesEntities;

class DummyConnectorFactory implements DevicesConnectors\ConnectorFactory
{

	public function getType(): string
	{
		return 'dummy';
	}

	public function create(
		DevicesEntities\Connectors\Connector $connector,
	): DevicesConnectors\Connector
	{
		return new DummyConnector();
	}

}
