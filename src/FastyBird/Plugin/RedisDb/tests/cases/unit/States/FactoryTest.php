<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Tests\Cases\Unit\States;

use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\States;
use Nette\Utils;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use stdClass;
use Throwable;
use function strval;

final class FactoryTest extends TestCase
{

	/**
	 * @phpstan-param Array<string, Array<string|Array<string, mixed>>> $data
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
			self::assertSame(strval($value), strval($formatted[$key]));
		}
	}

	/**
	 * @phpstan-param Array<string, Array<string|Array<string, mixed>>> $data
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
	 * @return Array<string, Array<string|Array<string, mixed>>>
	 */
	public function createStateValidDocumentData(): array
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
	 * @return Array<string, Array<string|Array<string, mixed>>>
	 */
	public function createStateInvalidDocumentData(): array
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
