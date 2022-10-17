<?php declare(strict_types = 1);

namespace FastyBird\ShellyConnector\Tests\Cases\Unit\DI;

use FastyBird\ShellyConnector\Hydrators;
use FastyBird\ShellyConnector\Schemas;
use FastyBird\ShellyConnector\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class ShellyConnectorExtensionTest extends BaseTestCase
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
