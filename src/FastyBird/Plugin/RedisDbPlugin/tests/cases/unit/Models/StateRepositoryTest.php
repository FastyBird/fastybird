<?php declare(strict_types = 1);

namespace FastyBird\RedisDbPlugin\Tests\Cases\Unit\Models;

use FastyBird\RedisDbPlugin\Client;
use FastyBird\RedisDbPlugin\Exceptions;
use FastyBird\RedisDbPlugin\Models;
use FastyBird\RedisDbPlugin\States;
use Nette\Utils;
use PHPUnit\Framework\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;

final class StateRepositoryTest extends TestCase
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
	 * @phpstan-param Array<mixed> $data
	 *
	 * @phpstan-return Client\Client&MockObject\MockObject
	 *
	 * @throws Utils\JsonException
	 */
	private function mockRedisWithData(
		Uuid\UuidInterface $id,
		array $data,
	): MockObject\MockObject
	{
		$data['_id'] = $data['id'];

		$redisClient = $this->createMock(Client\Client::class);
		$redisClient
			->expects(self::once())
			->method('select')
			->with(1);
		$redisClient
			->expects(self::once())
			->method('get')
			->with($id->toString())
			->willReturn(Utils\Json::encode($data));

		return $redisClient;
	}

	private function createRepository(
		Client\Client&MockObject\MockObject $redisClient,
	): Models\StatesRepository
	{
		return new Models\StatesRepository($redisClient);
	}

}
