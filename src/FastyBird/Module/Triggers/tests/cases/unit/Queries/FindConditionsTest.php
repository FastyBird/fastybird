<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Queries;
use Ramsey\Uuid;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../DbTestCase.php';

/**
 * @testCase
 */
final class FindConditionsQueryTest extends DbTestCase
{

	public function testFindById(): void
	{
		/** @var Models\Conditions\ConditionsRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Conditions\ConditionsRepository::class);

		$findQuery = new Queries\FindConditions();
		$findQuery->byId(Uuid\Uuid::fromString('2726f19c-7759-440e-b6f5-8c3306692fa2'));

		$entity = $repository->findOneBy($findQuery);

		Assert::true(is_object($entity));
		Assert::type(Entities\Conditions\ChannelPropertyCondition::class, $entity);
	}

	public function testFindForDevice(): void
	{
		/** @var Models\Conditions\ConditionsRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Conditions\ConditionsRepository::class);

		$findQuery = new Queries\FindConditions();
		$findQuery->forDevice(Uuid\Uuid::fromString('28989c89-e7d7-4664-9d18-a73647a844fb'));

		$entity = $repository->findOneBy($findQuery, Entities\Conditions\ChannelPropertyCondition::class);

		Assert::true(is_object($entity));
		Assert::type(Entities\Conditions\ChannelPropertyCondition::class, $entity);
	}

	public function testFindForChannel(): void
	{
		/** @var Models\Conditions\ConditionsRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Conditions\ConditionsRepository::class);

		$findQuery = new Queries\FindConditions();
		$findQuery->forChannel(Uuid\Uuid::fromString('5421c268-8f5d-4972-a7b5-6b4295c3e4b1'));

		$entity = $repository->findOneBy($findQuery, Entities\Conditions\ChannelPropertyCondition::class);

		Assert::true(is_object($entity));
		Assert::type(Entities\Conditions\ChannelPropertyCondition::class, $entity);
	}

	public function testFindForChannelProperty(): void
	{
		/** @var Models\Conditions\ConditionsRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Conditions\ConditionsRepository::class);

		$findQuery = new Queries\FindConditions();
		$findQuery->forProperty(Uuid\Uuid::fromString('ff7b36d7-a0b0-4336-9efb-a608c93b0974'));

		$entity = $repository->findOneBy($findQuery, Entities\Conditions\ChannelPropertyCondition::class);

		Assert::true(is_object($entity));
		Assert::type(Entities\Conditions\ChannelPropertyCondition::class, $entity);
	}

	public function testFindForCombination(): void
	{
		/** @var Models\Conditions\ConditionsRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Conditions\ConditionsRepository::class);

		$findQuery = new Queries\FindConditions();
		$findQuery->forDevice(Uuid\Uuid::fromString('28989c89-e7d7-4664-9d18-a73647a844fb'));
		$findQuery->forChannel(Uuid\Uuid::fromString('5421c268-8f5d-4972-a7b5-6b4295c3e4b1'));
		$findQuery->forProperty(Uuid\Uuid::fromString('ff7b36d7-a0b0-4336-9efb-a608c93b0974'));

		$entity = $repository->findOneBy($findQuery, Entities\Conditions\ChannelPropertyCondition::class);

		Assert::true(is_object($entity));
		Assert::type(Entities\Conditions\ChannelPropertyCondition::class, $entity);
	}

}

$test_case = new FindConditionsQueryTest();
$test_case->run();
