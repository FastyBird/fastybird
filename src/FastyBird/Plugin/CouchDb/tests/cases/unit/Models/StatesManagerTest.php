<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\Tests\Cases\Unit\Models;

use Consistence;
use DateTimeImmutable;
use FastyBird\DateTimeFactory;
use FastyBird\Plugin\CouchDb\Connections;
use FastyBird\Plugin\CouchDb\Exceptions;
use FastyBird\Plugin\CouchDb\Models;
use FastyBird\Plugin\CouchDb\States;
use FastyBird\Plugin\CouchDb\Tests\Fixtures;
use Nette\Utils;
use PHPOnCouch;
use PHPUnit\Framework\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;
use stdClass;
use function array_keys;
use function assert;
use const DATE_ATOM;

final class StatesManagerTest extends TestCase
{

	/**
	 * @phpstan-param array<Uuid\UuidInterface|array<string, mixed>> $data
	 * @phpstan-param array<Uuid\UuidInterface|array<string, mixed>> $dbData
	 * @phpstan-param array<Uuid\UuidInterface|array<string, mixed>> $expected
	 *
	 * @throws Exceptions\InvalidState
	 *
	 * @dataProvider createStateValue
	 */
	public function testCreateEntity(Uuid\UuidInterface $id, array $data, array $dbData, array $expected): void
	{
		$id = Uuid\Uuid::uuid4();

		$couchClient = $this->createMock(PHPOnCouch\CouchClient::class);
		$couchClient
			->method('storeDoc')
			->with(self::callback(static function (stdClass $create) use ($dbData): bool {
				foreach ($dbData as $key => $value) {
					if ($key !== 'id') {
						self::assertEquals($value, $create->$key);
					}
				}

				return true;
			}));
		$couchClient
			->method('asCouchDocuments');
		$couchClient
			->method('getDoc')
			->willReturnCallback(function () use ($dbData, $id): PHPOnCouch\CouchDocument {
				$dbData['id'] = $id->toString();

				$document = $this->createMock(PHPOnCouch\CouchDocument::class);
				assert($document instanceof MockObject\MockObject || $document instanceof PHPOnCouch\CouchDocument);

				$document
					->method('id')
					->willReturn($dbData['id']);
				$document
					->method('get')
					->willReturnCallback(static fn ($key) => $dbData[$key]);
				$document
					->method('getKeys')
					->willReturn(array_keys($dbData));

				return $document;
			});

		$couchDbConnection = $this->createMock(Connections\Connection::class);
		$couchDbConnection
			->method('getClient')
			->willReturn($couchClient);

		$dateTimeFactory = $this->createMock(DateTimeFactory\Factory::class);

		$manager = new Models\StatesManager($couchDbConnection, $dateTimeFactory, Fixtures\CustomState::class);

		$state = $manager->create($id, Utils\ArrayHash::from($data));

		$expected['id'] = $id->toString();

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
		$document = $this->createMock(PHPOnCouch\CouchDocument::class);
		assert($document instanceof MockObject\MockObject || $document instanceof PHPOnCouch\CouchDocument);

		$document
			->method('setAutocommit');
		$document
			->method('get')
			->willReturnCallback(static function ($key) use (&$originalData) {
				return $originalData[$key];
			});
		$document
			->method('set')
			->willReturnCallback(
				static function (string $key, string|null $value = null) use ($data, &$originalData): void {
					if ($data[$key] instanceof Consistence\Enum\Enum) {
						self::assertEquals((string) $data[$key], $value);

					} else {
						self::assertEquals($data[$key], $value);
					}

					$originalData[$key] = $value;
				},
			);
		$document
			->method('record');
		$document
			->method('getKeys')
			->willReturn(array_keys($originalData));
		$document
			->method('id')
			->willReturn($originalData['id']);

		$couchClient = $this->createMock(PHPOnCouch\CouchClient::class);
		$couchClient
			->method('asCouchDocuments');

		$couchDbConnection = $this->createMock(Connections\Connection::class);
		$couchDbConnection
			->method('getClient')
			->willReturn($couchClient);

		$dateTimeFactory = $this->createMock(DateTimeFactory\Factory::class);

		$manager = new Models\StatesManager($couchDbConnection, $dateTimeFactory, Fixtures\CustomState::class);

		$original = States\StateFactory::create(Fixtures\CustomState::class, $document);

		$state = $manager->update($original, Utils\ArrayHash::from($data));

		self::assertSame(Fixtures\CustomState::class, $state::class);
		self::assertEquals($expected, $state->toArray());
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function testDeleteEntity(): void
	{
		$id = Uuid\Uuid::uuid4();

		$originalData = [
			'id' => $id->toString(),
			'device' => 'device_name',
			'property' => 'property_name',
		];

		$document = $this->createMock(PHPOnCouch\CouchDocument::class);
		assert($document instanceof MockObject\MockObject || $document instanceof PHPOnCouch\CouchDocument);

		$document
			->method('get')
			->willReturnCallback(static function ($key) use (&$originalData) {
				return $originalData[$key];
			});
		$document
			->method('getKeys')
			->willReturn(array_keys($originalData));
		$document
			->method('id')
			->willReturn($originalData['id']);

		$couchClient = $this->createMock(PHPOnCouch\CouchClient::class);
		$couchClient
			->expects(self::once())
			->method('asCouchDocuments');
		$couchClient
			->expects(self::once())
			->method('getDoc')
			->with($originalData['id'])
			->willReturn($document);
		$couchClient
			->expects(self::once())
			->method('deleteDoc')
			->with($document);

		$couchDbConnection = $this->createMock(Connections\Connection::class);
		$couchDbConnection
			->method('getClient')
			->willReturn($couchClient);

		$dateTimeFactory = $this->createMock(DateTimeFactory\Factory::class);

		$manager = new Models\StatesManager($couchDbConnection, $dateTimeFactory, Fixtures\CustomState::class);

		$original = States\StateFactory::create(Fixtures\CustomState::class, $document);

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
					'camelCased' => null,
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
					'camelCased' => null,
					'created' => null,
				],
				[
					'id' => $id->toString(),
					'value' => null,
					'camelCased' => null,
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
					'camelCased' => null,
					'created' => $now->format(DATE_ATOM),
					'updated' => null,
				],
				[
					'updated' => $now->format(DATE_ATOM),
				],
				[
					'id' => $id->toString(),
					'value' => 'value',
					'camelCased' => null,
					'created' => $now->format(DATE_ATOM),
					'updated' => $now->format(DATE_ATOM),
				],
				[
					'id' => $id->toString(),
					'value' => 'value',
					'camelCased' => null,
					'created' => $now->format(DATE_ATOM),
					'updated' => $now->format(DATE_ATOM),
				],
			],
			'two' => [
				$id,
				[
					'id' => $id->toString(),
					'value' => 'value',
					'camelCased' => null,
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
					'camelCased' => 'camelCasedValue',
					'created' => $now->format(DATE_ATOM),
					'updated' => $now->format(DATE_ATOM),
				],
				[
					'id' => $id->toString(),
					'value' => 'updated',
					'camelCased' => 'camelCasedValue',
					'created' => $now->format(DATE_ATOM),
					'updated' => $now->format(DATE_ATOM),
				],
			],
		];
	}

}
