<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Tests\Fixtures\Dummy;

use FastyBird\Module\Devices\States as DevicesStates;
use Nette\Utils;
use Ramsey\Uuid;
use RuntimeException;

class DummyStatesManager
{

	/**
	 * @throws RuntimeException
	 */
	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): DevicesStates\Property
	{
		throw new RuntimeException('This is dummy service');
	}

	/**
	 * @throws RuntimeException
	 */
	public function update(DevicesStates\Property $state, Utils\ArrayHash $values): DevicesStates\Property
	{
		throw new RuntimeException('This is dummy service');
	}

	/**
	 * @throws RuntimeException
	 */
	public function updateState(DevicesStates\Property $state, Utils\ArrayHash $values): DevicesStates\Property
	{
		throw new RuntimeException('This is dummy service');
	}

	/**
	 * @throws RuntimeException
	 */
	public function delete(DevicesStates\Property $state): bool
	{
		throw new RuntimeException('This is dummy service');
	}

}
