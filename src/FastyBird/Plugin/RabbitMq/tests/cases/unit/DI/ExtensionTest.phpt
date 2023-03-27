<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\Plugin\RabbitMq;
use FastyBird\Plugin\RabbitMq\Connections;
use FastyBird\Plugin\RabbitMq\Consumer;
use FastyBird\Plugin\RabbitMq\Subscribers;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../BaseTestCase.php';

/**
 * @testCase
 */
final class ExtensionTest extends BaseTestCase
{

	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		Assert::notNull($container->getByType(RabbitMqPlugin\Exchange::class));

		Assert::notNull($container->getByType(Connections\Connection::class));

		Assert::notNull($container->getByType(Consumer\IConsumer::class));

		Assert::notNull($container->getByType(Subscribers\InitializeSubscriber::class));
	}

}

$test_case = new ExtensionTest();
$test_case->run();
