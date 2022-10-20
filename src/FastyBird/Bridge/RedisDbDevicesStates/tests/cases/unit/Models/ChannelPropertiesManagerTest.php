<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbDevicesStates\Tests\Cases\Unit\Models;

use Exception;
use FastyBird\Bridge\RedisDbDevicesStates\Models;
use FastyBird\Bridge\RedisDbDevicesStates\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Bridge\RedisDbDevicesStates\Tests\Tools\JsonAssert;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Plugin\RedisDb\Client as RedisDbClient;
use Nette\Utils;
use Ramsey\Uuid\Uuid;

final class ChannelPropertiesManagerTest extends BaseTestCase
{

	/**
	 * @throws Exception
	 */
	public function testCreate(): void
	{
		$id = Uuid::uuid4();

		$redisDbClient = $this->createMock(RedisDbClient\Client::class);

		$redisDbClient
			->expects(self::once())
			->method('select')
			->with(0);

		$redisDbClient
			->expects(self::once())
			->method('set')
			->with(
				$id->toString(),
				self::callback(function(string $data) use ($id): bool {
					JsonAssert::assertMatch(Utils\Json::encode([
						'id' => $id->toString(),
						'actual_value' => 10,
						'expected_value' => 20,
						'pending' => false,
						'valid' => false,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => null,
					]), $data);

					return true;
				}),
			)
			->willReturn(true);

		$redisDbClient
			->expects(self::once())
			->method('get')
			->with($id->toString())
			->willReturn(Utils\Json::encode([
				'id' => $id->toString(),
				'actual_value' => 10,
				'expected_value' => 20,
				'created_at' => '2020-04-01T12:00:00+00:00',
			]));

		$this->mockContainerService(RedisDbClient\Client::class, $redisDbClient);

		$manager = $this->container->getByType(Models\ChannelPropertiesManager::class);

		$property = $this->createMock(DevicesEntities\Channels\Properties\Dynamic::class);
		$property
			->expects(self::once())
			->method('getId')
			->willReturn($id);

		$state = $manager->create($property, Utils\ArrayHash::from([
			'actualValue' => 10,
			'expectedValue' => 20,
		]));

		self::assertSame(10, $state->getActualValue());
		self::assertSame(20, $state->getExpectedValue());
		self::assertNotNull($state->getCreatedAt());
	}

	/**
	 * @throws Exception
	 */
	public function testUpdate(): void
	{
		$id = Uuid::uuid4();

		$redisDbClient = $this->createMock(RedisDbClient\Client::class);

		$redisDbClient
			->expects(self::exactly(2))
			->method('select')
			->with(0);

		$redisDbClient
			->expects(self::once())
			->method('set')
			->with(
				$id->toString(),
				self::callback(function(string $data) use ($id): bool {
					JsonAssert::assertMatch(Utils\Json::encode([
						'id' => $id->toString(),
						'actual_value' => 10,
						'expected_value' => 40,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					]), $data);

					return true;
				}),
			)
			->willReturn(true);

		$redisDbClient
			->expects(self::exactly(2))
			->method('get')
			->with($id->toString())
			->willReturn(Utils\Json::encode([
				'id' => $id->toString(),
				'actual_value' => 10,
				'expected_value' => 40,
				'created_at' => '2020-04-01T12:00:00+00:00',
			]));

		$this->mockContainerService(RedisDbClient\Client::class, $redisDbClient);

		$property = $this->createMock(DevicesEntities\Channels\Properties\Dynamic::class);
		$property
			->expects(self::once())
			->method('getId')
			->willReturn($id);

		$repository = $this->container->getByType(Models\ChannelPropertiesRepository::class);

		$state = $repository->findOne($property);

		self::assertNotNull($state);

		$manager = $this->container->getByType(Models\ChannelPropertiesManager::class);

		$state = $manager->update($property, $state, Utils\ArrayHash::from([
			'expectedValue' => 40,
		]));

		self::assertSame(10, $state->getActualValue());
		self::assertSame(40, $state->getExpectedValue());
		self::assertNotNull($state->getCreatedAt());
	}

}
