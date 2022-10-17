<?php declare(strict_types = 1);

namespace FastyBird\DevicesModule\Tests\Fixtures\Dummy;

use FastyBird\DevicesModule\States;
use Ramsey\Uuid;
use RuntimeException;

class DummyStateRepository
{

	public function findOne(Uuid\UuidInterface $id): States\Property|null
	{
		throw new RuntimeException('Thi is dummy service');
	}

}
