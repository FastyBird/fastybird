<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Tests\Cases\Unit\Models;

use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\Models;
use FastyBird\Plugin\RedisDb\States;
use Nette\Utils;
use PHPUnit\Framework\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;

final class StatesRepositoryTest extends TestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Utils\JsonException
	 */
	public function testFetchEntity(): void
	{
		$id = Uuid\Uuid::uuid4();

		$data = [
			'id' => $id->toString(),
			'datatype' => null,
		];

		$redisClient = $this->mockRedisWithData($id, $data);

		$repository = $this->createRepository($redisClient);

		$state = $repository->findOne($id);

		self::assertIsObject($state, States\State::class);
	}

	/**
	 * @phpstan-param array<mixed> $data
	 *
	 * @phpstan-return Clients\Client&MockObject\MockObject
	 *
	 * @throws Utils\JsonException
	 */
	private function mockRedisWithData(
		Uuid\UuidInterface $id,
		array $data,
	): MockObject\MockObject
	{
		$data['_id'] = $data['id'];

		$redisClient = $this->createMock(Clients\Client::class);
		$redisClient
			->expects(self::once())
			->method('select')
			->with(0);
		$redisClient
			->expects(self::once())
			->method('get')
			->with($id->toString())
			->willReturn(Utils\Json::encode($data));

		return $redisClient;
	}

	/**
	 * @phpstan-return Models\StatesRepository<States\State>
	 */
	private function createRepository(
		Clients\Client&MockObject\MockObject $redisClient,
	): Models\StatesRepository
	{
		return new Models\StatesRepository($redisClient);
	}

}
