<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Tests\Cases\Unit\DI;

use FastyBird\Connector\FbMqtt\API;
use FastyBird\Connector\FbMqtt\Consumers;
use FastyBird\Connector\FbMqtt\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class FbMqttExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertNotNull($container->getByType(API\V1Parser::class, false));
		self::assertNotNull($container->getByType(API\V1Validator::class, false));

		self::assertNotNull($container->getByType(Consumers\Messages::class, false));
	}

}
