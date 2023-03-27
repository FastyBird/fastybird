<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\Plugin\RabbitMq\Connections;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../BaseTestCase.php';

/**
 * @testCase
 */
final class RabbitMqConnectionTest extends BaseTestCase
{

	public function testDefaultValues(): void
	{
		/** @var Connections\Connection $connection */
		$connection = $this->container->getByType(Connections\Connection::class);

		Assert::same('127.0.0.1', $connection->getHost());
		Assert::same(5672, $connection->getPort());
		Assert::same('/', $connection->getVhost());
		Assert::same('guest', $connection->getUsername());
		Assert::same('guest', $connection->getPassword());
	}

	public function testConfiguredValues(): void
	{
		$container = $this->createContainer(__DIR__ . '/../../../fixtures/Connections/customConnection.neon');

		/** @var Connections\Connection $connection */
		$connection = $container->getByType(Connections\Connection::class);

		Assert::same('rabbitmq.loc', $connection->getHost());
		Assert::same(1234, $connection->getPort());
	}

}

$test_case = new RabbitMqConnectionTest();
$test_case->run();
