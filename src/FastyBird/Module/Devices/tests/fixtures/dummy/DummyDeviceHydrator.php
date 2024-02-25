<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Fixtures\Dummy;

use FastyBird\Module\Devices\Hydrators;

final class DummyDeviceHydrator extends Hydrators\Devices\Device
{

	public function getEntityName(): string
	{
		return DummyDeviceEntity::class;
	}

}
