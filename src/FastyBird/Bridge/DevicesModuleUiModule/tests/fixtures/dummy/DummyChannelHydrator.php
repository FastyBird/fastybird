<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Fixtures\Dummy;

use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

final class DummyChannelHydrator extends DevicesHydrators\Channels\Channel
{

	public function getEntityName(): string
	{
		return DummyChannelEntity::class;
	}

}
