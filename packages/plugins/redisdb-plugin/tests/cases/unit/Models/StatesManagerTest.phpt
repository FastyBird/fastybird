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
use Tests\Fixtures;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class StatesManagerTest extends BaseMockeryTestCase
{

	/**
	 * @param Uuid\UuidInterface $id
	 * @param mixed[] $data
	 * @param mixed[] $dbData
	 * @param mixed[] $expected
	 *
	 * @dataProvider ./../../../fixtures/Models/createStateValue.php
	 */
	public function testCreateEntity(Uuid\UuidInterface $id, array $data, array $dbData, array $expected): void
	{
		$redisClient = Mockery::mock(Client\Client::class);
		$redisClient
			->shouldReceive('select')
			->with(1)
			->andReturns()
			->times(1)
			->getMock()
			->shouldReceive('set')
			->withArgs([$id->toString(), Utils\Json::encode($dbData)])
			->andReturn(true)
			->times(1)
			->getMock()
			->shouldReceive('get')
			->withArgs([$id->toString()])
			->andReturn(Utils\Json::encode($dbData))
			->times(1);

		$manager = new Models\StatesManager($redisClient, Fixtures\CustomState::class);

		$state = $manager->create($id, Utils\ArrayHash::from($data));

		Assert::type(Fixtures\CustomState::class, $state);
		Assert::equal($expected, $state->toArray());
	}

	/**
	 * @param Uuid\UuidInterface $id
	 * @param mixed[] $originalData
	 * @param mixed[] $data
	 * @param mixed[] $dbData
	 * @param mixed[] $expected
	 *
	 * @dataProvider ./../../../fixtures/Models/updateStateValue.php
	 */
	public function testUpdateEntity(
		Uuid\UuidInterface $id,
		array $originalData,
		array $data,
		array $dbData,
		array $expected
	): void {
		$redisClient = Mockery::mock(Client\Client::class);
		$redisClient
			->shouldReceive('select')
			->with(1)
			->andReturns()
			->times(1)
			->getMock()
			->shouldReceive('set')
			->withArgs([$id->toString(), Utils\Json::encode($expected)])
			->andReturn(true)
			->times(1)
			->getMock()
			->shouldReceive('get')
			->withArgs([$id->toString()])
			->andReturn(Utils\Json::encode($dbData))
			->times(1);

		$manager = new Models\StatesManager($redisClient, Fixtures\CustomState::class);

		$original = States\StateFactory::create(Fixtures\CustomState::class, Utils\Json::encode($originalData));

		$state = $manager->update($original, Utils\ArrayHash::from($data));

		Assert::type(States\State::class, $state);
		Assert::equal($expected, $state->toArray());
	}

	public function testDeleteEntity(): void
	{
		$id = Uuid\Uuid::uuid4();

		$originalData = [
			'id'       => $id->toString(),
			'device'   => 'device_name',
			'property' => 'property_name',
		];

		$redisClient = Mockery::mock(Client\Client::class);
		$redisClient
			->shouldReceive('select')
			->with(1)
			->andReturns()
			->times(1)
			->getMock()
			->shouldReceive('del')
			->withArgs([$id->toString()])
			->andReturn(true)
			->times(1);

		$manager = new Models\StatesManager($redisClient, Fixtures\CustomState::class);

		$original = new Fixtures\CustomState($originalData['id'], Utils\Json::encode($originalData));

		Assert::true($manager->delete($original));
	}

}

$test_case = new StatesManagerTest();
$test_case->run();
