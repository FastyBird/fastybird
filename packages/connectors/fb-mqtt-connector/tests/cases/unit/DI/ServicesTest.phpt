<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\FbMqttConnector\API;
use FastyBird\FbMqttConnector\Consumers;
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

		Assert::notNull($container->getByType(API\V1Parser::class));
		Assert::notNull($container->getByType(API\V1Validator::class));

		Assert::notNull($container->getByType(Consumers\Messages::class));
	}

}

$test_case = new ServicesTest();
$test_case->run();
