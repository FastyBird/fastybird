<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Cases\Unit\Models\Configuration\Repositories;

use Error;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Ui\Documents;
use FastyBird\Module\Ui\Exceptions;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Queries;
use FastyBird\Module\Ui\Tests;
use Nette;
use Ramsey\Uuid;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class WidgetDataSourcesRepositoryTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadOne(): void
	{
		$repository = $this->getContainer()->getByType(Models\Configuration\Widgets\DataSources\Repository::class);

		$findQuery = new Queries\Configuration\FindWidgetDataSources();
		$findQuery->byId(Uuid\Uuid::fromString('32dd50e4-4b66-4dea-9bc5-e835f8543dc4'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertInstanceOf(Documents\Widgets\DataSources\Generic::class, $entity);

		$findQuery = new Queries\Configuration\FindWidgetDataSources();
		$findQuery->byId(Uuid\Uuid::fromString('3333dd94-0605-45b5-ac51-c08cfb604e64'));

		$entity = $repository->findOneBy($findQuery);

		self::assertNull($entity);

		$findQuery = new Queries\Configuration\FindWidgetDataSources();
		$findQuery->byWidgetId(Uuid\Uuid::fromString('15553443-4564-454d-af04-0dfeef08aa96'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertInstanceOf(Documents\Widgets\DataSources\Generic::class, $entity);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadAll(): void
	{
		$repository = $this->getContainer()->getByType(Models\Configuration\Widgets\DataSources\Repository::class);

		$findQuery = new Queries\Configuration\FindWidgetDataSources();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(4, $entities);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadAllByWidget(): void
	{
		$devicesRepository = $this->getContainer()->getByType(Models\Configuration\Widgets\Repository::class);

		$findQuery = new Queries\Configuration\FindWidgets();
		$findQuery->byId(Uuid\Uuid::fromString('15553443-4564-454d-af04-0dfeef08aa96'));

		$widget = $devicesRepository->findOneBy($findQuery);

		self::assertInstanceOf(Documents\Widgets\Widget::class, $widget);
		self::assertSame('room-temperature', $widget->getIdentifier());

		$repository = $this->getContainer()->getByType(Models\Configuration\Widgets\DataSources\Repository::class);

		$findQuery = new Queries\Configuration\FindWidgetDataSources();
		$findQuery->forWidget($widget);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(1, $entities);
	}

}
