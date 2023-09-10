<?php declare(strict_types = 1);

namespace FastyBird\Automator\DevicesModule\Tests\Cases\Unit\Queries;

use Error;
use FastyBird\Automator\DevicesModule\Entities;
use FastyBird\Automator\DevicesModule\Exceptions;
use FastyBird\Automator\DevicesModule\Queries;
use FastyBird\Automator\DevicesModule\Tests\Cases\Unit\DbTestCase;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Module\Triggers\Models as TriggersModels;
use Nette;
use Ramsey\Uuid;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class FindActionsTest extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function XtestFindById(): void
	{
		$repository = $this->getContainer()->getByType(TriggersModels\Actions\ActionsRepository::class);

		$findQuery = new Queries\FindActions();
		$findQuery->byId(Uuid\Uuid::fromString('4aa84028-d8b7-4128-95b2-295763634aa4'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertTrue($entity instanceof Entities\Actions\ChannelPropertyAction);
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function XtestFindForDevice(): void
	{
		$repository = $this->getContainer()->getByType(TriggersModels\Actions\ActionsRepository::class);

		$findQuery = new Queries\FindActions();
		$findQuery->forDevice(Uuid\Uuid::fromString('a830828c-6768-4274-b909-20ce0e222347'));

		$entity = $repository->findOneBy($findQuery, Entities\Actions\ChannelPropertyAction::class);

		self::assertIsObject($entity);
		self::assertTrue($entity instanceof Entities\Actions\ChannelPropertyAction);
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function XtestFindForChannel(): void
	{
		$repository = $this->getContainer()->getByType(TriggersModels\Actions\ActionsRepository::class);

		$findQuery = new Queries\FindActions();
		$findQuery->forChannel(Uuid\Uuid::fromString('4f692f94-5be6-4384-94a7-60c424a5f723'));

		$entity = $repository->findOneBy($findQuery, Entities\Actions\ChannelPropertyAction::class);

		self::assertIsObject($entity);
		self::assertTrue($entity instanceof Entities\Actions\ChannelPropertyAction);
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function XtestFindForChannelProperty(): void
	{
		$repository = $this->getContainer()->getByType(TriggersModels\Actions\ActionsRepository::class);

		$findQuery = new Queries\FindActions();
		$findQuery->forProperty(Uuid\Uuid::fromString('7bc1fc81-8ace-409d-b044-810140e2361a'));

		$entity = $repository->findOneBy($findQuery, Entities\Actions\ChannelPropertyAction::class);

		self::assertIsObject($entity);
		self::assertTrue($entity instanceof Entities\Actions\ChannelPropertyAction);
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function XtestFindForCombination(): void
	{
		$repository = $this->getContainer()->getByType(TriggersModels\Actions\ActionsRepository::class);

		$findQuery = new Queries\FindActions();
		$findQuery->forDevice(Uuid\Uuid::fromString('a830828c-6768-4274-b909-20ce0e222347'));
		$findQuery->forChannel(Uuid\Uuid::fromString('4f692f94-5be6-4384-94a7-60c424a5f723'));
		$findQuery->forProperty(Uuid\Uuid::fromString('7bc1fc81-8ace-409d-b044-810140e2361a'));

		$entity = $repository->findOneBy($findQuery, Entities\Actions\ChannelPropertyAction::class);

		self::assertIsObject($entity);
		self::assertTrue($entity instanceof Entities\Actions\ChannelPropertyAction);
	}

}
