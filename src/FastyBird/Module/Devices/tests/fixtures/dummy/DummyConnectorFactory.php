<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Fixtures\Dummy;

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Module\Devices\Connectors;

class DummyConnectorFactory implements Connectors\ConnectorFactory
{

	public function getType(): string
	{
		return 'dummy';
	}

	public function create(
		MetadataDocuments\DevicesModule\Connector $connector,
	): Connectors\Connector
	{
		return new DummyConnector();
	}

}
