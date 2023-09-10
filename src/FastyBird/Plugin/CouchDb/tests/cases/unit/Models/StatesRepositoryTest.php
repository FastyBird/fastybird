<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\Tests\Cases\Unit\Models;

use FastyBird\Plugin\CouchDb\Connections;
use FastyBird\Plugin\CouchDb\Exceptions;
use FastyBird\Plugin\CouchDb\Models;
use FastyBird\Plugin\CouchDb\States;
use InvalidArgumentException;
use PHPOnCouch;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;

final class StatesRepositoryTest extends TestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 */
	public function XtestFetchEntity(): void
	{
		$id = Uuid\Uuid::uuid4();

		$data = [
			'id' => $id->toString(),
			'datatype' => null,
		];

		$couchClient = $this->mockCouchDbWithData($id, $data);

		$repository = $this->createRepository($couchClient);

		$state = $repository->findOne($id);

		self::assertIsObject($state, States\State::class);
	}

	/**
	 * @param array<mixed> $data
	 */
	private function mockCouchDbWithData(
		Uuid\UuidInterface $id,
		array $data,
	): Connections\Connection
	{
		$data['_id'] = $data['id'];

		$couchClient = $this->createMock(PHPOnCouch\CouchClient::class);
		$couchClient
			->method('asCouchDocuments');
		$couchClient
			->expects(self::once())
			->method('find')
			->with([
				'id' => [
					'$eq' => $id->toString(),
				],
			])
			->willReturn([(object) $data]);

		$couchDbConnection = $this->createMock(Connections\Connection::class);
		$couchDbConnection
			->method('getClient')
			->willReturn($couchClient);

		return $couchDbConnection;
	}

	/**
	 * @phpstan-return Models\StatesRepository<States\State>
	 */
	private function createRepository(
		Connections\Connection $couchClient,
	): Models\StatesRepository
	{
		return new Models\StatesRepository($couchClient);
	}

}
