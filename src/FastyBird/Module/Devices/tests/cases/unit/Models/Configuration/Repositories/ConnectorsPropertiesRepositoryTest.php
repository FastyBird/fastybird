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

final class ConnectorsPropertiesRepositoryTest extends DbTestCase
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
		$repository = $this->getContainer()->getByType(Models\Configuration\Connectors\Properties\Repository::class);

		$findQuery = new Queries\Configuration\FindConnectorProperties();
		$findQuery->byIdentifier('username');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('username', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindConnectorProperties();
		$findQuery->startWithIdentifier('user');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('username', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindConnectorProperties();
		$findQuery->endWithIdentifier('ame');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('username', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindConnectorProperties();
		$findQuery->byIdentifier('invalid');

		$entity = $repository->findOneBy($findQuery);

		self::assertNull($entity);

		$findQuery = new Queries\Configuration\FindConnectorProperties();
		$findQuery->byId(Uuid\Uuid::fromString('5a8b01f2-621c-4c41-bc83-c089d72b2366'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('username', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindConnectorProperties();
		$findQuery->byIdentifier('username');
		$findQuery->byConnectorId(Uuid\Uuid::fromString('17c59dfa-2edd-438e-8c49-faa4e38e5a5e'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('username', $entity->getIdentifier());
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
		$repository = $this->getContainer()->getByType(Models\Configuration\Connectors\Properties\Repository::class);

		$findQuery = new Queries\Configuration\FindConnectorProperties();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(2, $entities);

		$findQuery = new Queries\Configuration\FindConnectorProperties();

		$entities = $repository->findAllBy($findQuery, MetadataDocuments\DevicesModule\ConnectorDynamicProperty::class);

		self::assertCount(0, $entities);

		$findQuery = new Queries\Configuration\FindConnectorProperties();

		$entities = $repository->findAllBy(
			$findQuery,
			MetadataDocuments\DevicesModule\ConnectorVariableProperty::class,
		);

		self::assertCount(2, $entities);

		$findQuery = new Queries\Configuration\FindConnectorDynamicProperties();

		$entities = $repository->findAllBy($findQuery, MetadataDocuments\DevicesModule\ConnectorDynamicProperty::class);

		self::assertCount(0, $entities);

		$findQuery = new Queries\Configuration\FindConnectorVariableProperties();

		$entities = $repository->findAllBy(
			$findQuery,
			MetadataDocuments\DevicesModule\ConnectorVariableProperty::class,
		);

		self::assertCount(2, $entities);
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
	public function testReadAllByConnector(): void
	{
		$devicesRepository = $this->getContainer()->getByType(Models\Configuration\Connectors\Repository::class);

		$findQuery = new Queries\Configuration\FindConnectors();
		$findQuery->byId(Uuid\Uuid::fromString('17c59dfa-2edd-438e-8c49-faa4e38e5a5e'));

		$connector = $devicesRepository->findOneBy($findQuery);

		self::assertInstanceOf(MetadataDocuments\DevicesModule\Connector::class, $connector);
		self::assertSame('blank', $connector->getIdentifier());

		$repository = $this->getContainer()->getByType(Models\Configuration\Connectors\Properties\Repository::class);

		$findQuery = new Queries\Configuration\FindConnectorProperties();
		$findQuery->forConnector($connector);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(2, $entities);
	}

}
