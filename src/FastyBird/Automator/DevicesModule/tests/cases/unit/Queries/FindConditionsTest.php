<?php declare(strict_types = 1);

namespace FastyBird\Automator\DevicesModule\Tests\Cases\Unit\Queries;

use FastyBird\Automator\DevicesModule\Entities;
use FastyBird\Automator\DevicesModule\Exceptions;
use FastyBird\Automator\DevicesModule\Queries;
use FastyBird\Automator\DevicesModule\Tests\Cases\Unit\DbTestCase;
use FastyBird\Module\Triggers\Models as TriggersModels;
use Nette;
use Ramsey\Uuid;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class FindConditionsTest extends DbTestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testFindById(): void
	{
		$repository = $this->getContainer()->getByType(TriggersModels\Conditions\ConditionsRepository::class);

		$findQuery = new Queries\FindConditions();
		$findQuery->byId(Uuid\Uuid::fromString('2726f19c-7759-440e-b6f5-8c3306692fa2'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertTrue($entity instanceof Entities\Conditions\ChannelPropertyCondition);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testFindForDevice(): void
	{
		$repository = $this->getContainer()->getByType(TriggersModels\Conditions\ConditionsRepository::class);

		$findQuery = new Queries\FindConditions();
		$findQuery->forDevice(Uuid\Uuid::fromString('28989c89-e7d7-4664-9d18-a73647a844fb'));

		$entity = $repository->findOneBy($findQuery, Entities\Conditions\ChannelPropertyCondition::class);

		self::assertIsObject($entity);
		self::assertTrue($entity instanceof Entities\Conditions\ChannelPropertyCondition);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testFindForChannel(): void
	{
		$repository = $this->getContainer()->getByType(TriggersModels\Conditions\ConditionsRepository::class);

		$findQuery = new Queries\FindConditions();
		$findQuery->forChannel(Uuid\Uuid::fromString('5421c268-8f5d-4972-a7b5-6b4295c3e4b1'));

		$entity = $repository->findOneBy($findQuery, Entities\Conditions\ChannelPropertyCondition::class);

		self::assertIsObject($entity);
		self::assertTrue($entity instanceof Entities\Conditions\ChannelPropertyCondition);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testFindForChannelProperty(): void
	{
		$repository = $this->getContainer()->getByType(TriggersModels\Conditions\ConditionsRepository::class);

		$findQuery = new Queries\FindConditions();
		$findQuery->forProperty(Uuid\Uuid::fromString('ff7b36d7-a0b0-4336-9efb-a608c93b0974'));

		$entity = $repository->findOneBy($findQuery, Entities\Conditions\ChannelPropertyCondition::class);

		self::assertIsObject($entity);
		self::assertTrue($entity instanceof Entities\Conditions\ChannelPropertyCondition);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testFindForCombination(): void
	{
		$repository = $this->getContainer()->getByType(TriggersModels\Conditions\ConditionsRepository::class);

		$findQuery = new Queries\FindConditions();
		$findQuery->forDevice(Uuid\Uuid::fromString('28989c89-e7d7-4664-9d18-a73647a844fb'));
		$findQuery->forChannel(Uuid\Uuid::fromString('5421c268-8f5d-4972-a7b5-6b4295c3e4b1'));
		$findQuery->forProperty(Uuid\Uuid::fromString('ff7b36d7-a0b0-4336-9efb-a608c93b0974'));

		$entity = $repository->findOneBy($findQuery, Entities\Conditions\ChannelPropertyCondition::class);

		self::assertIsObject($entity);
		self::assertTrue($entity instanceof Entities\Conditions\ChannelPropertyCondition);
	}

}
