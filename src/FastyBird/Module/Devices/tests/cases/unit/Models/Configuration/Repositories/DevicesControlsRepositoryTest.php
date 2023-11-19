<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\Configuration\Repositories;

use Error;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Tests\Cases\Unit\DbTestCase;
use Nette;
use Ramsey\Uuid;
use RuntimeException;

final class DevicesControlsRepositoryTest extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadOne(): void
	{
		$builder = $this->getContainer()->getByType(Models\Configuration\Builder::class);
		$builder->build();

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
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadAll(): void
	{
		$builder = $this->getContainer()->getByType(Models\Configuration\Builder::class);
		$builder->build();

		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Controls\Repository::class);

		$findQuery = new Queries\Configuration\FindDeviceControls();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(1, $entities);
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadAllByDevice(): void
	{
		$builder = $this->getContainer()->getByType(Models\Configuration\Builder::class);
		$builder->build();

		$devicesRepository = $this->getContainer()->getByType(Models\Configuration\Devices\Repository::class);

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->byId(Uuid\Uuid::fromString('69786d15-fd0c-4d9f-9378-33287c2009fa'));

		$device = $devicesRepository->findOneBy($findQuery);

		self::assertInstanceOf(MetadataDocuments\DevicesModule\Device::class, $device);
		self::assertSame('first-device', $device->getIdentifier());

		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Controls\Repository::class);

		$findQuery = new Queries\Configuration\FindDeviceControls();
		$findQuery->forDevice($device);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(1, $entities);
	}

}
