<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\RedisDbPlugin\Client;
use FastyBird\RedisDbPlugin\Models;
use FastyBird\RedisDbPlugin\States;
use Mockery;
use Nette\Utils;
use Ninjify\Nunjuck\TestCase\BaseMockeryTestCase;
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

		$redisClient = $this->mockRedisWithData($id, $data);

		$repository = $this->createRepository($redisClient);

		$state = $repository->findOne($id);

		Assert::type(States\State::class, $state);
	}

	/**
	 * @param Uuid\UuidInterface $id
	 * @param mixed[] $data
	 *
	 * @return Mockery\MockInterface|Client\Client
	 */
	private function mockRedisWithData(
		Uuid\UuidInterface $id,
		array $data
	): Mockery\MockInterface {
		$data['_id'] = $data['id'];

		$redisClient = Mockery::mock(Client\Client::class);
		$redisClient
			->shouldReceive('select')
			->with(1)
			->andReturns()
			->times(1)
			->getMock()
			->shouldReceive('get')
			->with($id->toString())
			->andReturn(Utils\Json::encode($data))
			->times(1);

		return $redisClient;
	}

	/**
	 * @param Mockery\MockInterface|Client\Client $redisClient
	 *
	 * @return Models\StatesRepository
	 */
	private function createRepository(
		Mockery\MockInterface $redisClient
	): Models\StatesRepository {
		return new Models\StatesRepository($redisClient);
	}

}

$test_case = new StateRepositoryTest();
$test_case->run();
