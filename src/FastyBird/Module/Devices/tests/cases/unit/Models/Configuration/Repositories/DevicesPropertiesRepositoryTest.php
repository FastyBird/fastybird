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
use Nette\Utils;
use Orisai\DataSources;
use Ramsey\Uuid;
use RuntimeException;

final class DevicesPropertiesRepositoryTest extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Utils\JsonException
	 */
	public function setUp(): void
	{
		parent::setUp();

		$dataSource = $this->createMock(DataSources\DefaultDataSource::class);
		$dataSource
			->method('decode')
			->willReturn(
				Utils\Json::decode(
					Utils\FileSystem::read(__DIR__ . '/../../../../../fixtures/devices-module-data.json'),
				),
			);

		$this->mockContainerService(
			DataSources\DefaultDataSource::class,
			$dataSource,
		);
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
	public function testReadOne(): void
	{
		$builder = $this->getContainer()->getByType(Models\Configuration\Builder::class);
		$builder->build();

		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Properties\Repository::class);

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->byIdentifier('uptime');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('uptime', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->startWithIdentifier('up');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('uptime', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->endWithIdentifier('ime');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('uptime', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->byIdentifier('invalid');

		$entity = $repository->findOneBy($findQuery);

		self::assertNull($entity);

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->byId(Uuid\Uuid::fromString('bbcccf8c-33ab-431b-a795-d7bb38b6b6db'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('uptime', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->byIdentifier('uptime');
		$findQuery->byDeviceId(Uuid\Uuid::fromString('69786d15-fd0c4-d9f9-3783-3287c2009fa'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('uptime', $entity->getIdentifier());
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

		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Properties\Repository::class);

		$findQuery = new Queries\Configuration\FindDeviceProperties();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(15, $entities);

		$findQuery = new Queries\Configuration\FindDeviceProperties();

		$entities = $repository->findAllBy($findQuery, MetadataDocuments\DevicesModule\DeviceDynamicProperty::class);

		self::assertCount(2, $entities);

		$findQuery = new Queries\Configuration\FindDeviceProperties();

		$entities = $repository->findAllBy($findQuery, MetadataDocuments\DevicesModule\DeviceVariableProperty::class);

		self::assertCount(13, $entities);

		$findQuery = new Queries\Configuration\FindDeviceProperties();

		$entities = $repository->findAllBy($findQuery, MetadataDocuments\DevicesModule\DeviceMappedProperty::class);

		self::assertCount(0, $entities);

		$findQuery = new Queries\Configuration\FindDeviceDynamicProperties();

		$entities = $repository->findAllBy($findQuery, MetadataDocuments\DevicesModule\DeviceDynamicProperty::class);

		self::assertCount(2, $entities);

		$findQuery = new Queries\Configuration\FindDeviceVariableProperties();

		$entities = $repository->findAllBy($findQuery, MetadataDocuments\DevicesModule\DeviceVariableProperty::class);

		self::assertCount(13, $entities);

		$findQuery = new Queries\Configuration\FindDeviceMappedProperties();

		$entities = $repository->findAllBy($findQuery, MetadataDocuments\DevicesModule\DeviceMappedProperty::class);

		self::assertCount(0, $entities);
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
		$findQuery->byId(Uuid\Uuid::fromString('69786d15-fd0c4-d9f9-3783-3287c2009fa'));

		$device = $devicesRepository->findOneBy($findQuery);

		self::assertInstanceOf(MetadataDocuments\DevicesModule\Device::class, $device);
		self::assertSame('first-device', $device->getIdentifier());

		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Properties\Repository::class);

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->forDevice($device);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(10, $entities);
	}

}
