<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Fixtures\Dummy;

use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

final class DummyDeviceHydrator extends DevicesHydrators\Devices\Device
{

	public function getEntityName(): string
	{
		return DummyDeviceEntity::class;
	}

}
