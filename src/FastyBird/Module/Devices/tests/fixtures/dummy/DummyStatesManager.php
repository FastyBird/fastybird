<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Fixtures\Dummy;

use FastyBird\Module\Devices\States;
use Nette\Utils;
use Ramsey\Uuid;
use RuntimeException;

class DummyStatesManager
{

	/**
	 * @throws RuntimeException
	 */
	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\Property
	{
		throw new RuntimeException('This is dummy service');
	}

	/**
	 * @throws RuntimeException
	 */
	public function update(States\Property $state, Utils\ArrayHash $values): States\Property
	{
		throw new RuntimeException('This is dummy service');
	}

	/**
	 * @throws RuntimeException
	 */
	public function updateState(States\Property $state, Utils\ArrayHash $values): States\Property
	{
		throw new RuntimeException('This is dummy service');
	}

	/**
	 * @throws RuntimeException
	 */
	public function delete(States\Property $state): bool
	{
		throw new RuntimeException('This is dummy service');
	}

}
