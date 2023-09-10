<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Entities;

use Error;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Tests\Cases\Unit\DbTestCase;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette;
use Nette\Utils;
use RuntimeException;

final class DeviceEntitiesTest extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function XtestFindChildren(): void
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
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function XtestCreateChild(): void
	{
		$manager = $this->getContainer()->getByType(Models\Devices\DevicesManager::class);

		$repository = $this->getContainer()->getByType(Models\Devices\DevicesRepository::class);

		$findQuery = new Queries\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsObject($parent);
		self::assertSame('first-device', $parent->getIdentifier());

		$child = $manager->create(Utils\ArrayHash::from([
			'entity' => Entities\Devices\Blank::class,
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
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function XtestRemoveParent(): void
	{
		$manager = $this->getContainer()->getByType(Models\Devices\DevicesManager::class);

		$repository = $this->getContainer()->getByType(Models\Devices\DevicesRepository::class);

		$findQuery = new Queries\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsObject($parent);
		self::assertSame('first-device', $parent->getIdentifier());

		$manager->delete($parent);

		$findQuery = new Queries\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsNotObject($parent);

		$findQuery = new Queries\FindDevices();
		$findQuery->byIdentifier('child-device');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsNotObject($entity);
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function XtestChildParent(): void
	{
		$manager = $this->getContainer()->getByType(Models\Devices\DevicesManager::class);

		$repository = $this->getContainer()->getByType(Models\Devices\DevicesRepository::class);

		$findQuery = new Queries\FindDevices();
		$findQuery->byIdentifier('child-device');

		$child = $repository->findOneBy($findQuery);

		self::assertIsObject($child);
		self::assertSame('child-device', $child->getIdentifier());

		$manager->delete($child);

		$findQuery = new Queries\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsObject($parent);
		self::assertSame('first-device', $parent->getIdentifier());
	}

}
