<?php declare(strict_types = 1);

namespace Tests\Cases;

use Consistence;
use FastyBird\CouchDbPlugin\Connections;
use FastyBird\CouchDbPlugin\Models;
use FastyBird\CouchDbPlugin\States;
use Mockery;
use Nette\Utils;
use Ninjify\Nunjuck\TestCase\BaseMockeryTestCase;
use PHPOnCouch;
use Psr\Log;
use Ramsey\Uuid;
use stdClass;
use Tester\Assert;
use Tests\Fixtures;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class StatesManagerTest extends BaseMockeryTestCase
{

	/**
	 * @param mixed[] $data
	 * @param mixed[] $dbData
	 * @param mixed[] $expected
	 *
	 * @dataProvider ./../../../fixtures/Models/createStateValue.php
	 */
	public function testCreateEntity(array $data, array $dbData, array $expected): void
	{
		$id = Uuid\Uuid::uuid4();

		$couchDbClient = Mockery::mock(PHPOnCouch\CouchClient::class);
		$couchDbClient
			->shouldReceive('storeDoc')
			->withArgs(function (stdClass $create) use ($dbData): bool {
				foreach ($dbData as $key => $value) {
					Assert::equal($value, $create->$key);
				}

				return true;
			})
			->getMock()
			->shouldReceive('asCouchDocuments')
			->getMock()
			->shouldReceive('getDoc')
			->andReturnUsing(function () use ($dbData, $id): PHPOnCouch\CouchDocument {
				$dbData['id'] = $id->toString();

				/** @var Mockery\MockInterface|PHPOnCouch\CouchDocument $document */
				$document = Mockery::mock(PHPOnCouch\CouchDocument::class);
				$document
					->shouldReceive('id')
					->andReturn($dbData['id'])
					->getMock()
					->shouldReceive('get')
					->andReturnUsing(function ($key) use ($dbData) {
						return $dbData[$key];
					})
					->getMock()
					->shouldReceive('getKeys')
					->andReturn(array_keys($dbData));

				return $document;
			});

		$couchDbConnection = Mockery::mock(Connections\ICouchDbConnection::class);
		$couchDbConnection
			->shouldReceive('getClient')
			->andReturn($couchDbClient);

		$logger = Mockery::mock(Log\LoggerInterface::class);

		$manager = new Models\StatesManager($couchDbConnection, $logger);

		$state = $manager->create($id, Utils\ArrayHash::from($data), Fixtures\CustomState::class);

		$expected['id'] = $id->toString();

		Assert::type(Fixtures\CustomState::class, $state);
		Assert::equal($expected, $state->toArray());
	}

	/**
	 * @param mixed[] $data
	 * @param mixed[] $originalData
	 * @param mixed[] $expected
	 *
	 * @dataProvider ./../../../fixtures/Models/updateStateValue.php
	 */
	public function testUpdateEntity(array $data, array $originalData, array $expected): void
	{
		/** @var Mockery\MockInterface|PHPOnCouch\CouchDocument $document */
		$document = Mockery::mock(PHPOnCouch\CouchDocument::class);
		$document
			->shouldReceive('setAutocommit')
			->getMock()
			->shouldReceive('get')
			->andReturnUsing(function ($key) use (&$originalData) {
				return $originalData[$key];
			})
			->getMock()
			->shouldReceive('set')
			->withArgs(function ($key, $value) use ($data, &$originalData): bool {
				if ($data[$key] instanceof Consistence\Enum\Enum) {
					Assert::equal((string) $data[$key], $value);

				} else {
					Assert::equal($data[$key], $value);
				}

				$originalData[$key] = $value;

				return true;
			})
			->getMock()
			->shouldReceive('record')
			->getMock()
			->shouldReceive('getKeys')
			->andReturn(array_keys($originalData))
			->getMock()
			->shouldReceive('id')
			->andReturn($originalData['id']);

		$couchDbClient = Mockery::mock(PHPOnCouch\CouchClient::class);
		$couchDbClient
			->shouldReceive('asCouchDocuments');

		$couchDbConnection = Mockery::mock(Connections\ICouchDbConnection::class);
		$couchDbConnection
			->shouldReceive('getClient')
			->andReturn($couchDbClient);

		$logger = Mockery::mock(Log\LoggerInterface::class);

		$manager = new Models\StatesManager($couchDbConnection, $logger);

		$original = new Fixtures\CustomState($originalData['id'], $document);
		$original->setValue('value');

		$state = $manager->update($original, Utils\ArrayHash::from($data));

		Assert::type(States\State::class, $state);
		Assert::equal($expected, $state->toArray());
	}

	public function testDeleteEntity(): void
	{
		$originalData = [
			'id'       => Uuid\Uuid::uuid4()
				->toString(),
			'device'   => 'device_name',
			'property' => 'property_name',
		];

		/** @var Mockery\MockInterface|PHPOnCouch\CouchDocument $document */
		$document = Mockery::mock(PHPOnCouch\CouchDocument::class);

		$couchDbClient = Mockery::mock(PHPOnCouch\CouchClient::class);
		$couchDbClient
			->shouldReceive('asCouchDocuments')
			->times(1)
			->getMock()
			->shouldReceive('getDoc')
			->withArgs([$originalData['id']])
			->andReturn($document)
			->times(1)
			->getMock()
			->shouldReceive('deleteDoc')
			->withArgs([$document])
			->times(1);

		$couchDbConnection = Mockery::mock(Connections\ICouchDbConnection::class);
		$couchDbConnection
			->shouldReceive('getClient')
			->andReturn($couchDbClient);

		$logger = Mockery::mock(Log\LoggerInterface::class);

		$manager = new Models\StatesManager($couchDbConnection, $logger);

		$original = new Fixtures\CustomState($originalData['id'], $document);

		Assert::true($manager->delete($original));
	}

}

$test_case = new StatesManagerTest();
$test_case->run();
