<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit\Models\Repositories;

use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Queries;
use FastyBird\Module\Triggers\Tests\Cases\Unit\DbTestCase;
use FastyBird\Module\Triggers\Tests\Fixtures\Dummy\DummyConditionEntity;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette;
use Ramsey\Uuid;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ConditionsRepositoryTest extends DbTestCase
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
		$repository = $this->getContainer()->getByType(Models\Conditions\ConditionsRepository::class);

		$findQuery = new Queries\FindConditions();
		$findQuery->byId(Uuid\Uuid::fromString('09c453b3-c55f-4050-8f1c-b50f8d5728c2'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertTrue($entity instanceof DummyConditionEntity);
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
		$repository = $this->getContainer()->getByType(Models\Conditions\ConditionsRepository::class);

		$findQuery = new Queries\FindConditions();

		$resultSet = $repository->getResultSet($findQuery);

		self::assertSame(3, $resultSet->getTotalCount());
	}

}
