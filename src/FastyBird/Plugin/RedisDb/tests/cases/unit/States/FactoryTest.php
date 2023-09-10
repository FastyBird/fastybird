<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Tests\Cases\Unit\States;

use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\States;
use FastyBird\Plugin\RedisDb\Tests\Fixtures;
use Nette\Utils;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use stdClass;
use Throwable;

final class FactoryTest extends TestCase
{

	/**
	 * @phpstan-param class-string<Fixtures\CustomState> $class
	 * @phpstan-param array<string, array<string|array<string, mixed>>> $data
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Utils\JsonException
	 *
	 * @dataProvider createStateValidDocumentData
	 */
	public function testCreateEntity(string $class, array $data): void
	{
		$raw = new stdClass();

		foreach ($data as $key => $value) {
			$raw->$key = $value;
		}

		$entity = States\StateFactory::create($class, Utils\Json::encode($raw));

		self::assertTrue($entity instanceof $class);

		$formatted = $entity->toArray();

		foreach ($data as $key => $value) {
			self::assertSame($value, $formatted[$key]);
		}
	}

	/**
	 * @phpstan-param class-string<Fixtures\CustomState> $class
	 * @phpstan-param array<string, array<string|array<string, mixed>>> $data
	 * @phpstan-param class-string<Throwable> $exception
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Utils\JsonException
	 *
	 * @dataProvider createStateInvalidDocumentData
	 */
	public function testCreateEntityFail(string $class, array $data, string $exception): void
	{
		$raw = new stdClass();

		foreach ($data as $key => $value) {
			$raw->$key = $value;
		}

		$this->expectException($exception);

		States\StateFactory::create($class, Utils\Json::encode($raw));
	}

	/**
	 * @return array<string, array<string|array<string, mixed>>>
	 */
	public static function createStateValidDocumentData(): array
	{
		return [
			'one' => [
				States\State::class,
				[
					'id' => Uuid::uuid4()->toString(),
				],
			],
			'two' => [
				States\State::class,
				[
					'id' => Uuid::uuid4()->toString(),
				],
			],
		];
	}

	/**
	 * @return array<string, array<string|array<string, mixed>>>
	 */
	public static function createStateInvalidDocumentData(): array
	{
		return [
			'one' => [
				States\State::class,
				[],
				Exceptions\InvalidArgument::class,
			],
			'two' => [
				States\State::class,
				[
					'id' => 'invalid-string',
				],
				Exceptions\InvalidState::class,
			],
		];
	}

}
