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

final class ConnectorsControlsRepositoryTest extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
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

		$repository = $this->getContainer()->getByType(Models\Configuration\Connectors\Controls\Repository::class);

		$findQuery = new Queries\Configuration\FindConnectorControls();
		$findQuery->byName('search');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('search', $entity->getName());

		$findQuery = new Queries\Configuration\FindConnectorControls();
		$findQuery->byName('invalid');

		$entity = $repository->findOneBy($findQuery);

		self::assertNull($entity);

		$findQuery = new Queries\Configuration\FindConnectorControls();
		$findQuery->byId(Uuid\Uuid::fromString('7c055b2b-60c3-4017-93db-e9478d8aa662'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('search', $entity->getName());

		$findQuery = new Queries\Configuration\FindConnectorControls();
		$findQuery->byConnectorId(Uuid\Uuid::fromString('17c59dfa-2edd-438e-8c49-faa4e38e5a5e'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('search', $entity->getName());
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
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

		$repository = $this->getContainer()->getByType(Models\Configuration\Connectors\Controls\Repository::class);

		$findQuery = new Queries\Configuration\FindConnectorControls();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(1, $entities);
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadAllByConnector(): void
	{
		$builder = $this->getContainer()->getByType(Models\Configuration\Builder::class);
		$builder->clean();

		$devicesRepository = $this->getContainer()->getByType(Models\Configuration\Connectors\Repository::class);

		$findQuery = new Queries\Configuration\FindConnectors();
		$findQuery->byId(Uuid\Uuid::fromString('17c59dfa-2edd-438e-8c49-faa4e38e5a5e'));

		$connector = $devicesRepository->findOneBy($findQuery);

		self::assertInstanceOf(MetadataDocuments\DevicesModule\Connector::class, $connector);
		self::assertSame('blank', $connector->getIdentifier());

		$repository = $this->getContainer()->getByType(Models\Configuration\Connectors\Controls\Repository::class);

		$findQuery = new Queries\Configuration\FindConnectorControls();
		$findQuery->forConnector($connector);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(1, $entities);
	}

}
