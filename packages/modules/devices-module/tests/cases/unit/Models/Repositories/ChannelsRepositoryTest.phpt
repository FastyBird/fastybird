<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Models;
use FastyBird\DevicesModule\Queries;
use IPub\DoctrineOrmQuery;
use Tester\Assert;

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/../../DbTestCase.php';

/**
 * @testCase
 */
final class ChannelsRepositoryTest extends DbTestCase
{

	public function testReadOne(): void
	{
		/** @var Models\Channels\ChannelsRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Channels\ChannelsRepository::class);

		$findQuery = new Queries\FindChannels();
		$findQuery->byIdentifier('channel-one');

		$entity = $repository->findOneBy($findQuery);

		Assert::true(is_object($entity));
		Assert::type(Entities\Channels\Channel::class, $entity);
		Assert::same('channel-one', $entity->getIdentifier());
	}

	public function testReadResultSet(): void
	{
		/** @var Models\Channels\ChannelsRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Channels\ChannelsRepository::class);

		$findQuery = new Queries\FindChannels();

		$resultSet = $repository->getResultSet($findQuery);

		Assert::type(DoctrineOrmQuery\ResultSet::class, $resultSet);
		Assert::same(3, $resultSet->getTotalCount());
	}

}

$test_case = new ChannelsRepositoryTest();
$test_case->run();
