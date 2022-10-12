<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\TuyaConnector\Hydrators;
use FastyBird\TuyaConnector\Schemas;
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

		Assert::notNull($container->getByType(Hydrators\TuyaConnector::class));
		Assert::notNull($container->getByType(Hydrators\TuyaDevice::class));

		Assert::notNull($container->getByType(Schemas\TuyaConnector::class));
		Assert::notNull($container->getByType(Schemas\TuyaDevice::class));
	}

}

$test_case = new ServicesTest();
$test_case->run();
