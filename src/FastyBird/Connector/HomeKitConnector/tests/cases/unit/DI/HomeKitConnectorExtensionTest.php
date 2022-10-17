<?php declare(strict_types = 1);

namespace FastyBird\HomekitConnector\Tests\Cases\Unit\DI;

use FastyBird\HomeKitConnector\Hydrators;
use FastyBird\HomeKitConnector\Schemas;
use FastyBird\HomekitConnector\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class HomeKitConnectorExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertNotNull($container->getByType(Schemas\HomeKitDevice::class, false));
		self::assertNotNull($container->getByType(Schemas\HomeKitConnector::class, false));

		self::assertNotNull($container->getByType(Hydrators\HomeKitDevice::class, false));
		self::assertNotNull($container->getByType(Hydrators\HomeKitConnector::class, false));
	}

}
