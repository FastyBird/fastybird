<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Fixtures\Dummy;

use FastyBird\Module\Devices\Hydrators;

final class DummyConnectorHydrator extends Hydrators\Connectors\Connector
{

	public function getEntityName(): string
	{
		return DummyConnectorEntity::class;
	}

}
