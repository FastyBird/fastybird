<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\CouchDbStoragePlugin\Connections;
use FastyBird\CouchDbStoragePlugin\Models;
use FastyBird\CouchDbStoragePlugin\States;
use Mockery;
use Ninjify\Nunjuck\TestCase\BaseMockeryTestCase;
use PHPOnCouch;
use Psr\Log;
use Ramsey\Uuid;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class StateRepositoryTest extends BaseMockeryTestCase
{

	public function testFetchEntity(): void
	{
		$id = Uuid\Uuid::uuid4();

		$data = [
			'id'       => $id->toString(),
			'datatype' => null,
		];

		$couchDbClient = $this->mockCouchDbWithDocument($id, $data);

		$repository = $this->createRepository($couchDbClient);

		$state = $repository->findOne($id);

		Assert::type(States\State::class, $state);
	}

	/**
	 * @param Uuid\UuidInterface $id
	 * @param mixed[] $data
	 *
	 * @return Mockery\MockInterface|Connections\ICouchDbConnection
	 */
	private function mockCouchDbWithDocument(
		Uuid\UuidInterface $id,
		array $data
	): Mockery\MockInterface {
		$data['_id'] = $data['id'];

		$couchDbClient = Mockery::mock(PHPOnCouch\CouchClient::class);
		$couchDbClient
			->shouldReceive('asCouchDocuments')
			->getMock()
			->shouldReceive('find')
			->with([
				'id' => [
					'$eq' => $id->toString(),
				],
			])
			->andReturn([(object) $data])
			->times(1);

		$couchDbConnection = Mockery::mock(Connections\ICouchDbConnection::class);
		$couchDbConnection
			->shouldReceive('getClient')
			->andReturn($couchDbClient);

		return $couchDbConnection;
	}

	/**
	 * @param Mockery\MockInterface|Connections\ICouchDbConnection $couchDbClient
	 *
	 * @return Models\StateRepository
	 */
	private function createRepository(
		Mockery\MockInterface $couchDbClient
	): Models\StateRepository {
		$logger = Mockery::mock(Log\LoggerInterface::class);

		return new Models\StateRepository($couchDbClient, $logger);
	}

}

$test_case = new StateRepositoryTest();
$test_case->run();
