<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit\Entities\Actions;

use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Queries;
use FastyBird\Module\Triggers\Tests\Cases\Unit\DbTestCase;
use Nette;
use Ramsey\Uuid;
use RuntimeException;

final class ActionTest extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testValidation(): void
	{
		$repository = $this->getContainer()->getByType(Models\Actions\ActionsRepository::class);

		$findQuery = new Queries\FindActions();
		$findQuery->byId(Uuid\Uuid::fromString('4aa84028-d8b7-4128-95b2-295763634aa4'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertTrue($entity instanceof Entities\Actions\ChannelPropertyAction);

		self::assertTrue($entity->validate('on'));
		self::assertFalse($entity->validate('off'));
	}

}
