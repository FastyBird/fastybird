<?php declare(strict_types = 1);

namespace FastyBird\DevicesModule\Tests\Fixtures\Dummy;

use FastyBird\DevicesModule\States;
use Nette\Utils;
use Ramsey\Uuid;
use RuntimeException;

class DummyStatesManager
{

	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\Property
	{
		throw new RuntimeException('Thi is dummy service');
	}

	public function update(States\Property $state, Utils\ArrayHash $values): States\Property
	{
		throw new RuntimeException('Thi is dummy service');
	}

	public function updateState(States\Property $state, Utils\ArrayHash $values): States\Property
	{
		throw new RuntimeException('Thi is dummy service');
	}

	public function delete(States\Property $state): bool
	{
		throw new RuntimeException('Thi is dummy service');
	}

}
