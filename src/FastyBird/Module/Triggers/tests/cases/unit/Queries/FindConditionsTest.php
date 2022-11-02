<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit\Queries;

use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Queries;
use FastyBird\Module\Triggers\Tests\Cases\Unit\DbTestCase;
use Nette;
use Ramsey\Uuid;
use RuntimeException;

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
		$repository = $this->getContainer()->getByType(Models\Conditions\ConditionsRepository::class);

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
		$repository = $this->getContainer()->getByType(Models\Conditions\ConditionsRepository::class);

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
		$repository = $this->getContainer()->getByType(Models\Conditions\ConditionsRepository::class);

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
		$repository = $this->getContainer()->getByType(Models\Conditions\ConditionsRepository::class);

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
		$repository = $this->getContainer()->getByType(Models\Conditions\ConditionsRepository::class);

		$findQuery = new Queries\FindConditions();
		$findQuery->forDevice(Uuid\Uuid::fromString('28989c89-e7d7-4664-9d18-a73647a844fb'));
		$findQuery->forChannel(Uuid\Uuid::fromString('5421c268-8f5d-4972-a7b5-6b4295c3e4b1'));
		$findQuery->forProperty(Uuid\Uuid::fromString('ff7b36d7-a0b0-4336-9efb-a608c93b0974'));

		$entity = $repository->findOneBy($findQuery, Entities\Conditions\ChannelPropertyCondition::class);

		self::assertIsObject($entity);
		self::assertTrue($entity instanceof Entities\Conditions\ChannelPropertyCondition);
	}

}
