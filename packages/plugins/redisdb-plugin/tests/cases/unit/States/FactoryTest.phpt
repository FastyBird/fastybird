<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\RedisDbPlugin\Exceptions;
use FastyBird\RedisDbPlugin\States;
use Nette\Utils;
use Ninjify\Nunjuck\TestCase\BaseMockeryTestCase;
use stdClass;
use Tester\Assert;
use Throwable;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class FactoryTest extends BaseMockeryTestCase
{

	/**
	 * @param string $class
	 * @param mixed[] $data
	 *
	 * @dataProvider ./../../../fixtures/States/createStateValidDocumentData.php
	 */
	public function testCreateEntity(string $class, array $data): void
	{
		$raw = new stdClass();

		foreach ($data as $key => $value) {
			$raw->$key = $value;
		}

		$entity = States\StateFactory::create($class, Utils\Json::encode($raw));

		Assert::true($entity instanceof $class);

		if (method_exists($entity, 'toArray')) {
			$formatted = $entity->toArray();

		} else {
			$formatted = [];
		}

		foreach ($data as $key => $value) {
			Assert::same((string) $value, (string) $formatted[$key]);
		}
	}

	/**
	 * @param string $class
	 * @param mixed[] $data
	 *
	 * @dataProvider ./../../../fixtures/States/createStateInvalidDocumentData.php
	 */
	public function testCreateEntityFail(string $class, array $data): void
	{
		$raw = new stdClass();

		foreach ($data as $key => $value) {
			$raw->$key = $value;
		}

		try {
			States\StateFactory::create($class, Utils\Json::encode($raw));
		} catch (Throwable $ex) {
			if ($ex instanceof Exceptions\InvalidState) {
				Assert::true(true);
			} elseif ($ex instanceof Exceptions\InvalidArgument) {
				Assert::true(true);
			}
		}
	}

}

$test_case = new FactoryTest();
$test_case->run();
