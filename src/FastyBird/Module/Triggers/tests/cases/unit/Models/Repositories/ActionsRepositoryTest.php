<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Queries;
use IPub\DoctrineOrmQuery;
use Ramsey\Uuid;
use Tester\Assert;

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/../../DbTestCase.php';

/**
 * @testCase
 */
final class ActionsRepositoryTest extends DbTestCase
{

	public function testReadOne(): void
	{
		/** @var Models\Actions\ActionsRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Actions\ActionsRepository::class);

		$findQuery = new Queries\FindActions();
		$findQuery->byId(Uuid\Uuid::fromString('4aa84028-d8b7-4128-95b2-295763634aa4'));

		$entity = $repository->findOneBy($findQuery);

		Assert::true(is_object($entity));
		Assert::type(Entities\Actions\ChannelPropertyAction::class, $entity);
	}

	public function testReadResultSet(): void
	{
		/** @var Models\Actions\ActionsRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Actions\ActionsRepository::class);

		$findQuery = new Queries\FindActions();

		$resultSet = $repository->getResultSet($findQuery);

		Assert::type(DoctrineOrmQuery\ResultSet::class, $resultSet);
		Assert::same(13, $resultSet->getTotalCount());
	}

}

$test_case = new ActionsRepositoryTest();
$test_case->run();
