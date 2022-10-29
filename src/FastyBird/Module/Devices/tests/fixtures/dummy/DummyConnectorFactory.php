<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Fixtures\Dummy;

use FastyBird\Module\Devices\Connectors;
use FastyBird\Module\Devices\Entities;

class DummyConnectorFactory implements Connectors\ConnectorFactory
{

	public function getType(): string
	{
		return 'dummy';
	}

	public function create(
		Entities\Connectors\Connector $connector,
	): Connectors\Connector
	{
		return new DummyConnector();
	}

}
