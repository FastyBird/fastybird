<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Fixtures\Dummy;

use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

class DummyConnectorFactory implements DevicesConnectors\ConnectorFactory
{

	public static function getType(): string
	{
		return 'dummy';
	}

	public function create(DevicesDocuments\Connectors\Connector $connector): DevicesConnectors\Connector
	{
		return new DummyConnector();
	}

}
