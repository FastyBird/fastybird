<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\Configuration\Repositories;

use Error;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Tests\Cases\Unit\DbTestCase;
use Nette;
use Ramsey\Uuid;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class DevicesControlsRepositoryTest extends DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadOne(): void
	{
		$builder = $this->getContainer()->getByType(Models\Configuration\Builder::class);
		$builder->clean();

		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Controls\Repository::class);

		$findQuery = new Queries\Configuration\FindDeviceControls();
		$findQuery->byName('configure');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('configure', $entity->getName());

		$findQuery = new Queries\Configuration\FindDeviceControls();
		$findQuery->byName('invalid');

		$entity = $repository->findOneBy($findQuery);

		self::assertNull($entity);

		$findQuery = new Queries\Configuration\FindDeviceControls();
		$findQuery->byId(Uuid\Uuid::fromString('7c055b2b-60c3-4017-93db-e9478d8aa662'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('configure', $entity->getName());

		$findQuery = new Queries\Configuration\FindDeviceControls();
		$findQuery->byDeviceId(Uuid\Uuid::fromString('69786d15-fd0c-4d9f-9378-33287c2009fa'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('configure', $entity->getName());
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadAll(): void
	{
		$builder = $this->getContainer()->getByType(Models\Configuration\Builder::class);
		$builder->clean();

		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Controls\Repository::class);

		$findQuery = new Queries\Configuration\FindDeviceControls();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(1, $entities);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadAllByDevice(): void
	{
		$builder = $this->getContainer()->getByType(Models\Configuration\Builder::class);
		$builder->clean();

		$devicesRepository = $this->getContainer()->getByType(Models\Configuration\Devices\Repository::class);

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->byId(Uuid\Uuid::fromString('69786d15-fd0c-4d9f-9378-33287c2009fa'));

		$device = $devicesRepository->findOneBy($findQuery);

		self::assertInstanceOf(Documents\Devices\Device::class, $device);
		self::assertSame('first-device', $device->getIdentifier());

		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Controls\Repository::class);

		$findQuery = new Queries\Configuration\FindDeviceControls();
		$findQuery->forDevice($device);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(1, $entities);
	}

}
