<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Fixtures\Dummy;

use FastyBird\Module\Devices\States;
use Ramsey\Uuid;
use RuntimeException;

class DummyStateRepository
{

	public function findOne(Uuid\UuidInterface $id): States\Property|null
	{
		throw new RuntimeException('Thi is dummy service');
	}

}
