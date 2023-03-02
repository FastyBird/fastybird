<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Tests\Cases\Unit\Models;

use DateTimeImmutable;
use FastyBird\DateTimeFactory;
use FastyBird\Plugin\RedisDb\Client;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\Models;
use FastyBird\Plugin\RedisDb\States;
use FastyBird\Plugin\RedisDb\Tests\Fixtures;
use Nette\Utils;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;
use const DATE_ATOM;

final class StatesManagerTest extends TestCase
{

	/**
	 * @phpstan-param array<Uuid\UuidInterface|array<string, mixed>> $data
	 * @phpstan-param array<Uuid\UuidInterface|array<string, mixed>> $dbData
	 * @phpstan-param array<Uuid\UuidInterface|array<string, mixed>> $expected
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Utils\JsonException
	 *
	 * @dataProvider createStateValue
	 */
	public function testCreateEntity(Uuid\UuidInterface $id, array $data, array $dbData, array $expected): void
	{
		$redisClient = $this->createMock(Client\Client::class);
		$redisClient
			->expects(self::once())
			->method('select')
			->with(0);
		$redisClient
			->expects(self::once())
			->method('set')
			->with($id->toString(), Utils\Json::encode($dbData))
			->willReturn(true);
		$redisClient
			->expects(self::once())
			->method('get')
			->with($id->toString())
			->willReturn(Utils\Json::encode($dbData));

		$dateTimeFactory = $this->createMock(DateTimeFactory\Factory::class);

		$manager = new Models\StatesManager($redisClient, $dateTimeFactory, Fixtures\CustomState::class);

		$state = $manager->create($id, Utils\ArrayHash::from($data));

		self::assertSame(Fixtures\CustomState::class, $state::class);
		self::assertEquals($expected, $state->toArray());
	}

	/**
	 * @phpstan-param array<Uuid\UuidInterface|array<string, mixed>> $originalData
	 * @phpstan-param array<Uuid\UuidInterface|array<string, mixed>> $data
	 * @phpstan-param array<Uuid\UuidInterface|array<string, mixed>> $dbData
	 * @phpstan-param array<Uuid\UuidInterface|array<string, mixed>> $expected
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Utils\JsonException
	 *
	 * @dataProvider updateStateValue
	 */
	public function testUpdateEntity(
		Uuid\UuidInterface $id,
		array $originalData,
		array $data,
		array $dbData,
		array $expected,
	): void
	{
		$redisClient = $this->createMock(Client\Client::class);
		$redisClient
			->expects(self::once())
			->method('select')
			->with(0);
		$redisClient
			->expects(self::once())
			->method('set')
			->with($id->toString(), Utils\Json::encode($dbData))
			->willReturn(true);
		$redisClient
			->expects(self::once())
			->method('get')
			->with($id->toString())
			->willReturn(Utils\Json::encode($dbData));

		$dateTimeFactory = $this->createMock(DateTimeFactory\Factory::class);

		$manager = new Models\StatesManager($redisClient, $dateTimeFactory, Fixtures\CustomState::class);

		$original = States\StateFactory::create(Fixtures\CustomState::class, Utils\Json::encode($originalData));

		$state = $manager->update($original, Utils\ArrayHash::from($data));

		self::assertSame(Fixtures\CustomState::class, $state::class);
		self::assertEquals($expected, $state->toArray());
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Utils\JsonException
	 */
	public function testDeleteEntity(): void
	{
		$id = Uuid\Uuid::uuid4();

		$originalData = [
			'id' => $id->toString(),
			'device' => 'device_name',
			'property' => 'property_name',
		];

		$redisClient = $this->createMock(Client\Client::class);
		$redisClient
			->expects(self::once())
			->method('select')
			->with(0);
		$redisClient
			->expects(self::once())
			->method('del')
			->with($id->toString())
			->willReturn(true);

		$dateTimeFactory = $this->createMock(DateTimeFactory\Factory::class);

		$manager = new Models\StatesManager($redisClient, $dateTimeFactory, Fixtures\CustomState::class);

		$original = new Fixtures\CustomState($originalData['id'], Utils\Json::encode($originalData));

		self::assertTrue($manager->delete($original));
	}

	/**
	 * @return array<string, array<Uuid\UuidInterface|array<string, mixed>>>
	 */
	public static function createStateValue(): array
	{
		$id = Uuid\Uuid::uuid4();

		return [
			'one' => [
				$id,
				[
					'value' => 'keyValue',
				],
				[
					'id' => $id->toString(),
					'value' => 'keyValue',
					'camel_cased' => null,
					'created' => null,
				],
				[
					'id' => $id->toString(),
					'value' => 'keyValue',
					'camel_cased' => null,
					'created' => null,
					'updated' => null,
				],
			],
			'two' => [
				$id,
				[
					'id' => $id->toString(),
					'value' => null,
				],
				[
					'id' => $id->toString(),
					'value' => null,
					'camel_cased' => null,
					'created' => null,
				],
				[
					'id' => $id->toString(),
					'value' => null,
					'camel_cased' => null,
					'created' => null,
					'updated' => null,
				],
			],
		];
	}

	/**
	 * @return array<string, array<Uuid\UuidInterface|array<string, mixed>>>
	 */
	public static function updateStateValue(): array
	{
		$id = Uuid\Uuid::uuid4();
		$now = new DateTimeImmutable();

		return [
			'one' => [
				$id,
				[
					'id' => $id->toString(),
					'value' => 'value',
					'camel_cased' => null,
					'created' => $now->format(DATE_ATOM),
					'updated' => null,
				],
				[
					'updated' => $now->format(DATE_ATOM),
				],
				[
					'id' => $id->toString(),
					'value' => 'value',
					'camel_cased' => null,
					'created' => $now->format(DATE_ATOM),
					'updated' => $now->format(DATE_ATOM),
				],
				[
					'id' => $id->toString(),
					'value' => 'value',
					'camel_cased' => null,
					'created' => $now->format(DATE_ATOM),
					'updated' => $now->format(DATE_ATOM),
				],
			],
			'two' => [
				$id,
				[
					'id' => $id->toString(),
					'value' => 'value',
					'camel_cased' => null,
					'created' => $now->format(DATE_ATOM),
					'updated' => null,
				],
				[
					'updated' => $now->format(DATE_ATOM),
					'value' => 'updated',
					'camelCased' => 'camelCasedValue',
				],
				[
					'id' => $id->toString(),
					'value' => 'updated',
					'camel_cased' => 'camelCasedValue',
					'created' => $now->format(DATE_ATOM),
					'updated' => $now->format(DATE_ATOM),
				],
				[
					'id' => $id->toString(),
					'value' => 'updated',
					'camel_cased' => 'camelCasedValue',
					'created' => $now->format(DATE_ATOM),
					'updated' => $now->format(DATE_ATOM),
				],
			],
		];
	}

}
