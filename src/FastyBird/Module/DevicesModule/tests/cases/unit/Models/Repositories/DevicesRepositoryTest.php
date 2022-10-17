<?php declare(strict_types = 1);

namespace FastyBird\DevicesModule\Tests\Cases\Unit\Models\Repositories;

use FastyBird\DevicesModule\Exceptions;
use FastyBird\DevicesModule\Models;
use FastyBird\DevicesModule\Queries;
use FastyBird\DevicesModule\Tests\Cases\Unit\DbTestCase;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette;
use RuntimeException;

final class DevicesRepositoryTest extends DbTestCase
{

	/**
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testReadOne(): void
	{
		$repository = $this->getContainer()->getByType(Models\Devices\DevicesRepository::class);

		$findQuery = new Queries\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsObject($parent);
		self::assertSame('first-device', $parent->getIdentifier());

		$findQuery = new Queries\FindDevices();
		$findQuery->forParent($parent);

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('child-device', $entity->getIdentifier());
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testReadResultSet(): void
	{
		$repository = $this->getContainer()->getByType(Models\Devices\DevicesRepository::class);

		$findQuery = new Queries\FindDevices();

		$resultSet = $repository->getResultSet($findQuery);

		self::assertSame(4, $resultSet->getTotalCount());
	}

}
