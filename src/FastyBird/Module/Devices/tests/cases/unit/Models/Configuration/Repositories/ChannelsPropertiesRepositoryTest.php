<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\Configuration\Repositories;

use Error;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Tests\Cases\Unit\DbTestCase;
use Nette;
use Ramsey\Uuid;
use RuntimeException;

final class ChannelsPropertiesRepositoryTest extends DbTestCase
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
		$repository = $this->getContainer()->getByType(Models\Configuration\Channels\Properties\Repository::class);

		$findQuery = new Queries\Configuration\FindChannelProperties();
		$findQuery->byIdentifier('switch');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('switch', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindChannelProperties();
		$findQuery->startWithIdentifier('swi');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('switch', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindChannelProperties();
		$findQuery->endWithIdentifier('ture');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('temperature', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindChannelProperties();
		$findQuery->byIdentifier('invalid');

		$entity = $repository->findOneBy($findQuery);

		self::assertNull($entity);

		$findQuery = new Queries\Configuration\FindChannelProperties();
		$findQuery->byId(Uuid\Uuid::fromString('bbcccf8c-33ab-431b-a795-d7bb38b6b6db'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('switch', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindChannelProperties();
		$findQuery->byChannelId(Uuid\Uuid::fromString('17c59dfa-2edd-438e-8c49-faa4e38e5a5e'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('switch', $entity->getIdentifier());
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
		$repository = $this->getContainer()->getByType(Models\Configuration\Channels\Properties\Repository::class);

		$findQuery = new Queries\Configuration\FindChannelProperties();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(3, $entities);

		$findQuery = new Queries\Configuration\FindChannelProperties();

		$entities = $repository->findAllBy($findQuery, MetadataEntities\DevicesModule\ChannelDynamicProperty::class);

		self::assertCount(3, $entities);

		$findQuery = new Queries\Configuration\FindChannelProperties();

		$entities = $repository->findAllBy($findQuery, MetadataEntities\DevicesModule\ChannelVariableProperty::class);

		self::assertCount(0, $entities);

		$findQuery = new Queries\Configuration\FindChannelProperties();

		$entities = $repository->findAllBy($findQuery, MetadataEntities\DevicesModule\ChannelMappedProperty::class);

		self::assertCount(0, $entities);

		$findQuery = new Queries\Configuration\FindChannelProperties();

		$entities = $repository->findAllBy($findQuery, [
			MetadataEntities\DevicesModule\ChannelVariableProperty::class,
			MetadataEntities\DevicesModule\ChannelMappedProperty::class,
		]);

		self::assertCount(0, $entities);

		$findQuery = new Queries\Configuration\FindChannelDynamicProperties();

		$entities = $repository->findAllBy($findQuery, MetadataEntities\DevicesModule\ChannelDynamicProperty::class);

		self::assertCount(3, $entities);

		$findQuery = new Queries\Configuration\FindChannelVariableProperties();

		$entities = $repository->findAllBy($findQuery, MetadataEntities\DevicesModule\ChannelVariableProperty::class);

		self::assertCount(0, $entities);

		$findQuery = new Queries\Configuration\FindChannelMappedProperties();

		$entities = $repository->findAllBy($findQuery, MetadataEntities\DevicesModule\ChannelMappedProperty::class);

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
	public function testReadAllByChannel(): void
	{
		$devicesRepository = $this->getContainer()->getByType(Models\Configuration\Channels\Repository::class);

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->byId(Uuid\Uuid::fromString('17c59dfa-2edd-438e-8c49-faa4e38e5a5e'));

		$channel = $devicesRepository->findOneBy($findQuery);

		self::assertInstanceOf(MetadataEntities\DevicesModule\Channel::class, $channel);
		self::assertSame('channel-one', $channel->getIdentifier());

		$repository = $this->getContainer()->getByType(Models\Configuration\Channels\Properties\Repository::class);

		$findQuery = new Queries\Configuration\FindChannelProperties();
		$findQuery->forChannel($channel);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(1, $entities);
	}

}
