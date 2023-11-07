<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbDevicesModule\Tests\Cases\Unit\Models;

use Exception;
use FastyBird\Bridge\RedisDbDevicesModule\Models;
use FastyBird\Bridge\RedisDbDevicesModule\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Plugin\RedisDb\Clients as RedisDbClient;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use Nette;
use Nette\Utils;
use Ramsey\Uuid\Uuid;

final class ChannelPropertiesRepositoryTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 * @throws Exception
	 */
	public function testFindOne(): void
	{
		$id = Uuid::uuid4();

		$redisDbClient = $this->createMock(RedisDbClient\Client::class);

		$redisDbClient
			->expects(self::once())
			->method('select')
			->with(0);

		$redisDbClient
			->expects(self::once())
			->method('get')
			->with($id->toString())
			->willReturn(Utils\Json::encode([
				'id' => $id->toString(),
				'actual_value' => 10,
				'expected_value' => 20,
			]));

		$this->mockContainerService(RedisDbClient\Client::class, $redisDbClient);

		$repository = $this->container->getByType(Models\States\ChannelPropertiesRepository::class);

		$state = $repository->findOneById($id);

		self::assertIsObject($state);
		self::assertSame($id->toString(), $state->getId()->toString());
		self::assertSame(10, $state->getActualValue());
		self::assertSame(20, $state->getExpectedValue());
		self::assertNull($state->getCreatedAt());
		self::assertNull($state->getUpdatedAt());
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 * @throws Exception
	 */
	public function testFindOneById(): void
	{
		$id = Uuid::uuid4();

		$redisDbClient = $this->createMock(RedisDbClient\Client::class);

		$redisDbClient
			->expects(self::once())
			->method('select')
			->with(0);

		$redisDbClient
			->expects(self::once())
			->method('get')
			->with($id->toString())
			->willReturn(Utils\Json::encode([
				'id' => $id->toString(),
				'actual_value' => 10,
				'expected_value' => 20,
			]));

		$this->mockContainerService(RedisDbClient\Client::class, $redisDbClient);

		$repository = $this->container->getByType(Models\States\ChannelPropertiesRepository::class);

		$property = $this->createMock(DevicesEntities\Channels\Properties\Dynamic::class);
		$property
			->expects(self::once())
			->method('getId')
			->willReturn($id);

		$state = $repository->findOne($property);

		self::assertIsObject($state);
		self::assertSame($id->toString(), $state->getId()->toString());
		self::assertSame(10, $state->getActualValue());
		self::assertSame(20, $state->getExpectedValue());
		self::assertNull($state->getCreatedAt());
		self::assertNull($state->getUpdatedAt());
	}

}
