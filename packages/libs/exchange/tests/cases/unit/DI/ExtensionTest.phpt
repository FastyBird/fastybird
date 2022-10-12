<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\Exchange\Consumer;
use FastyBird\Exchange\Entities;
use FastyBird\Exchange\Publisher;
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

		Assert::notNull($container->getByType(Entities\EntityFactory::class));
		Assert::notNull($container->getByType(Publisher\Container::class));
		Assert::notNull($container->getByType(Consumer\Container::class));
	}

}

$test_case = new ExtensionTest();
$test_case->run();
