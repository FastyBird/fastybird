<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Tests\Cases\Unit\DI;

use FastyBird\Connector\Shelly\Hydrators;
use FastyBird\Connector\Shelly\Schemas;
use FastyBird\Connector\Shelly\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class ShellyExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertNotNull($container->getByType(Schemas\ShellyDevice::class, false));
		self::assertNotNull($container->getByType(Schemas\ShellyConnector::class, false));

		self::assertNotNull($container->getByType(Hydrators\ShellyDevice::class, false));
		self::assertNotNull($container->getByType(Hydrators\ShellyConnector::class, false));
	}

}
