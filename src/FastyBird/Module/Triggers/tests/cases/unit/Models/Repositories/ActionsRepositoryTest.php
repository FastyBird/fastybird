<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit\Models\Repositories;

use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Queries;
use FastyBird\Module\Triggers\Tests\Cases\Unit\DbTestCase;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette;
use Ramsey\Uuid;
use RuntimeException;

final class ActionsRepositoryTest extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testReadOne(): void
	{
		$repository = $this->getContainer()->getByType(Models\Actions\ActionsRepository::class);

		$findQuery = new Queries\FindActions();
		$findQuery->byId(Uuid\Uuid::fromString('4aa84028-d8b7-4128-95b2-295763634aa4'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertTrue($entity instanceof Entities\Actions\ChannelPropertyAction);
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testReadResultSet(): void
	{
		$repository = $this->getContainer()->getByType(Models\Actions\ActionsRepository::class);

		$findQuery = new Queries\FindActions();

		$resultSet = $repository->getResultSet($findQuery);

		self::assertSame(13, $resultSet->getTotalCount());
	}

}
