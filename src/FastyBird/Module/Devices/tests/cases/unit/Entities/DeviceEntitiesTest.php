<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Entities;

use Error;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Tests\Cases\Unit\DbTestCase;
use FastyBird\Module\Devices\Tests\Fixtures;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette;
use Nette\Utils;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class DeviceEntitiesTest extends DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testFindChildren(): void
	{
		$repository = $this->getContainer()->getByType(Models\Entities\Devices\DevicesRepository::class);

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsObject($parent);
		self::assertSame('first-device', $parent->getIdentifier());

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->forParent($parent);

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('child-device', $entity->getIdentifier());
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testCreateChild(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Devices\DevicesManager::class);

		$repository = $this->getContainer()->getByType(Models\Entities\Devices\DevicesRepository::class);

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsObject($parent);
		self::assertSame('first-device', $parent->getIdentifier());

		$child = $manager->create(Utils\ArrayHash::from([
			'entity' => Fixtures\Dummy\DummyDeviceEntity::class,
			'identifier' => 'new-child-device',
			'connector' => $parent->getConnector(),
			'name' => 'New child device',
			'parents' => [
				$parent,
			],
		]));

		self::assertSame('new-child-device', $child->getIdentifier());
		self::assertCount(1, $child->getParents());
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testRemoveParent(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Devices\DevicesManager::class);

		$repository = $this->getContainer()->getByType(Models\Entities\Devices\DevicesRepository::class);

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsObject($parent);
		self::assertSame('first-device', $parent->getIdentifier());

		$manager->delete($parent);

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsNotObject($parent);

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->byIdentifier('child-device');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsNotObject($entity);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testChildParent(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Devices\DevicesManager::class);

		$repository = $this->getContainer()->getByType(Models\Entities\Devices\DevicesRepository::class);

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->byIdentifier('child-device');

		$child = $repository->findOneBy($findQuery);

		self::assertIsObject($child);
		self::assertSame('child-device', $child->getIdentifier());

		$manager->delete($child);

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsObject($parent);
		self::assertSame('first-device', $parent->getIdentifier());
	}

}
