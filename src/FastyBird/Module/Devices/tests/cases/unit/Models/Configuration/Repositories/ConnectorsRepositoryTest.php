<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\Configuration\Repositories;

use Error;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Tests;
use Nette;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ConnectorsRepositoryTest extends Tests\Cases\Unit\DbTestCase
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

		$repository = $this->getContainer()->getByType(Models\Configuration\Connectors\Repository::class);

		$findQuery = new Queries\Configuration\FindConnectors();
		$findQuery->byIdentifier('dummy');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('dummy', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindConnectors();
		$findQuery->byTypes(['dummy', 'unknown']);

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('generic', $entity->getIdentifier());
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

		$repository = $this->getContainer()->getByType(Models\Configuration\Connectors\Repository::class);

		$findQuery = new Queries\Configuration\FindConnectors();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(2, $entities);
	}

}
