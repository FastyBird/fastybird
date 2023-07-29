<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\Tests\Cases\Unit\States;

use FastyBird\Plugin\CouchDb\Exceptions;
use FastyBird\Plugin\CouchDb\States;
use FastyBird\Plugin\CouchDb\Tests\Fixtures;
use InvalidArgumentException;
use PHPOnCouch;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Throwable;
use function array_keys;

final class FactoryTest extends TestCase
{

	/**
	 * @phpstan-param class-string<Fixtures\CustomState> $class
	 * @phpstan-param array<string, array<string|array<string, mixed>>> $data
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 *
	 * @dataProvider createStateValidDocumentData
	 */
	public function testCreateEntity(string $class, array $data): void
	{
		$document = $this->createMock(PHPOnCouch\CouchDocument::class);
		$document
			->method('getKeys')
			->willReturn(array_keys($data));
		$document
			->method('get')
			->willReturnCallback(static fn ($key) => $data[$key]);
		$document
			->method('id')
			->willReturn($data['id']);

		$entity = States\StateFactory::create($class, $document);

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
	 * @throws InvalidArgumentException
	 *
	 * @dataProvider createStateInvalidDocumentData
	 */
	public function testCreateEntityFail(string $class, array $data, string $exception): void
	{
		$document = $this->createMock(PHPOnCouch\CouchDocument::class);
		$document
			->method('getKeys')
			->willReturn(array_keys($data));
		$document
			->method('get')
			->willReturnCallback(static fn ($key) => $data[$key]);

		$this->expectException($exception);

		States\StateFactory::create($class, $document);
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
				Exceptions\InvalidState::class,
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
