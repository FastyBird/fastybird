<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Fixtures\Dummy;

use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

final class DummyConnectorHydrator extends DevicesHydrators\Connectors\Connector
{

	public function getEntityName(): string
	{
		return DummyConnectorEntity::class;
	}

}
