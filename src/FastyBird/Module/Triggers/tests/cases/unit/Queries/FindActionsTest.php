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
final class FindActionsQueryTest extends DbTestCase
{

	public function testFindById(): void
	{
		/** @var Models\Actions\ActionsRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Actions\ActionsRepository::class);

		$findQuery = new Queries\FindActions();
		$findQuery->byId(Uuid\Uuid::fromString('4aa84028-d8b7-4128-95b2-295763634aa4'));

		$entity = $repository->findOneBy($findQuery);

		Assert::true(is_object($entity));
		Assert::type(Entities\Actions\ChannelPropertyAction::class, $entity);
	}

	public function testFindForDevice(): void
	{
		/** @var Models\Actions\ActionsRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Actions\ActionsRepository::class);

		$findQuery = new Queries\FindActions();
		$findQuery->forDevice(Uuid\Uuid::fromString('a830828c-6768-4274-b909-20ce0e222347'));

		$entity = $repository->findOneBy($findQuery, Entities\Actions\ChannelPropertyAction::class);

		Assert::true(is_object($entity));
		Assert::type(Entities\Actions\ChannelPropertyAction::class, $entity);
	}

	public function testFindForChannel(): void
	{
		/** @var Models\Actions\ActionsRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Actions\ActionsRepository::class);

		$findQuery = new Queries\FindActions();
		$findQuery->forChannel(Uuid\Uuid::fromString('4f692f94-5be6-4384-94a7-60c424a5f723'));

		$entity = $repository->findOneBy($findQuery, Entities\Actions\ChannelPropertyAction::class);

		Assert::true(is_object($entity));
		Assert::type(Entities\Actions\ChannelPropertyAction::class, $entity);
	}

	public function testFindForChannelProperty(): void
	{
		/** @var Models\Actions\ActionsRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Actions\ActionsRepository::class);

		$findQuery = new Queries\FindActions();
		$findQuery->forProperty(Uuid\Uuid::fromString('7bc1fc81-8ace-409d-b044-810140e2361a'));

		$entity = $repository->findOneBy($findQuery, Entities\Actions\ChannelPropertyAction::class);

		Assert::true(is_object($entity));
		Assert::type(Entities\Actions\ChannelPropertyAction::class, $entity);
	}

	public function testFindForCombination(): void
	{
		/** @var Models\Actions\ActionsRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Actions\ActionsRepository::class);

		$findQuery = new Queries\FindActions();
		$findQuery->forDevice(Uuid\Uuid::fromString('a830828c-6768-4274-b909-20ce0e222347'));
		$findQuery->forChannel(Uuid\Uuid::fromString('4f692f94-5be6-4384-94a7-60c424a5f723'));
		$findQuery->forProperty(Uuid\Uuid::fromString('7bc1fc81-8ace-409d-b044-810140e2361a'));

		$entity = $repository->findOneBy($findQuery, Entities\Actions\ChannelPropertyAction::class);

		Assert::true(is_object($entity));
		Assert::type(Entities\Actions\ChannelPropertyAction::class, $entity);
	}

}

$test_case = new FindActionsQueryTest();
$test_case->run();
