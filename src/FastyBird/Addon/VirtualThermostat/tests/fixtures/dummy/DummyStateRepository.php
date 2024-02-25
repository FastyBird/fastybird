<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Tests\Fixtures\Dummy;

use FastyBird\Module\Devices\States as DevicesStates;
use Ramsey\Uuid;
use RuntimeException;

class DummyStateRepository
{

	/**
	 * @throws RuntimeException
	 */
	public function findOne(Uuid\UuidInterface $id): DevicesStates\Property|null
	{
		throw new RuntimeException('This is dummy service');
	}

}
