<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Fixtures\Dummy;

use FastyBird\Module\Devices\Hydrators;

final class DummyChannelHydrator extends Hydrators\Channels\Channel
{

	public function getEntityName(): string
	{
		return DummyChannelEntity::class;
	}

}
