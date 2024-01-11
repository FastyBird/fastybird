<?php declare(strict_types = 1);

namespace FastyBird\Addon\ThermostatDevice\Tests\Fixtures\Dummy;

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Module\Devices\Connectors as DevicesConnectors;

class DummyConnectorFactory implements DevicesConnectors\ConnectorFactory
{

	public function getType(): string
	{
		return 'dummy';
	}

	public function create(
		MetadataDocuments\DevicesModule\Connector $connector,
	): DevicesConnectors\Connector
	{
		return new DummyConnector();
	}

}
