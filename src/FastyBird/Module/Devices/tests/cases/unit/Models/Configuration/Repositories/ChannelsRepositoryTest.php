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

final class ChannelsRepositoryTest extends DbTestCase
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

		$repository = $this->getContainer()->getByType(Models\Configuration\Channels\Repository::class);

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->byIdentifier('channel-one');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('channel-one', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->startWithIdentifier('channel-o');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('channel-one', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->endWithIdentifier('two');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('channel-two', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->byIdentifier('invalid');

		$entity = $repository->findOneBy($findQuery);

		self::assertNull($entity);

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->byId(Uuid\Uuid::fromString('6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('channel-two', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->byDeviceId(Uuid\Uuid::fromString('bf4cd870-2aac-45f0-a85e-e1cefd2d6d9a'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('channel-one', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->byDeviceId(Uuid\Uuid::fromString('bf4cd870-2aac-45f0-a85e-e1cefd2d6d9a'));
		$findQuery->byTypes(['dummy', 'unknown']);

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('channel-one', $entity->getIdentifier());
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

		$repository = $this->getContainer()->getByType(Models\Configuration\Channels\Repository::class);

		$findQuery = new Queries\Configuration\FindChannels();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(3, $entities);
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
		$findQuery->byIdentifier('first-device');

		$device = $devicesRepository->findOneBy($findQuery);

		self::assertInstanceOf(Documents\Devices\Device::class, $device);
		self::assertSame('69786d15-fd0c-4d9f-9378-33287c2009fa', $device->getId()->toString());

		$repository = $this->getContainer()->getByType(Models\Configuration\Channels\Repository::class);

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->forDevice($device);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(2, $entities);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadAllWithProperties(): void
	{
		$builder = $this->getContainer()->getByType(Models\Configuration\Builder::class);
		$builder->clean();

		$repository = $this->getContainer()->getByType(Models\Configuration\Channels\Repository::class);

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->withProperties();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(2, $entities);
	}

}
