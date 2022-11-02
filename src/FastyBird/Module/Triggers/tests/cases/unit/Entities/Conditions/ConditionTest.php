<?php declare(strict_types = 1);

namespace Tests\Cases;

use DateTime;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Queries;
use Ramsey\Uuid;
use Tester\Assert;

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/../../DbTestCase.php';

/**
 * @testCase
 */
final class ConditionEntityTest extends DbTestCase
{

	public function testTimeConditionValidation(): void
	{
		/** @var Models\Conditions\ConditionsRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Conditions\ConditionsRepository::class);

		$findQuery = new Queries\FindConditions();
		$findQuery->byId(Uuid\Uuid::fromString('09c453b3-c55f-4050-8f1c-b50f8d5728c2'));

		$entity = $repository->findOneBy($findQuery);

		Assert::true(is_object($entity));
		Assert::type(Entities\Conditions\TimeCondition::class, $entity);

		Assert::true($entity->validate(new DateTime('1970-01-01T07:30:00+00:00')));
		Assert::true($entity->validate(new DateTime('07:30:00+00:00')));
		Assert::true($entity->validate(new DateTime('07:30:00')));

		Assert::false($entity->validate(new DateTime('1970-01-01T07:31:00+00:00')));
		Assert::false($entity->validate(new DateTime('07:31:00+00:00')));
		Assert::false($entity->validate(new DateTime('07:31:00')));
	}

	public function testPropertyConditionValidation(): void
	{
		/** @var Models\Conditions\ConditionsRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Conditions\ConditionsRepository::class);

		$findQuery = new Queries\FindConditions();
		$findQuery->byId(Uuid\Uuid::fromString('2726f19c-7759-440e-b6f5-8c3306692fa2'));

		$entity = $repository->findOneBy($findQuery);

		Assert::true(is_object($entity));
		Assert::type(Entities\Conditions\ChannelPropertyCondition::class, $entity);

		Assert::true($entity->validate('3'));

		Assert::false($entity->validate('1'));
	}

}

$test_case = new ConditionEntityTest();
$test_case->run();
