<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\WsExchangePlugin\Controllers;
use FastyBird\WsExchangePlugin\Subscribers;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../BaseTestCase.php';

/**
 * @testCase
 */
final class ServicesTest extends BaseTestCase
{

	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		// Assert::notNull($container->getByType(Subscribers\ApplicationSubscriber::class));
		Assert::notNull($container->getByType(Subscribers\Client::class));

		Assert::notNull($container->getByType(Controllers\Exchange::class));
	}

}

$test_case = new ServicesTest();
$test_case->run();
