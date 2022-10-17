<?php declare(strict_types = 1);

namespace FastyBird\ModbusConnector\Tests\Cases\Unit\DI;

use FastyBird\ModbusConnector\Hydrators;
use FastyBird\ModbusConnector\Schemas;
use FastyBird\ModbusConnector\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class ModbusConnectorExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertNotNull($container->getByType(Schemas\ModbusDevice::class, false));
		self::assertNotNull($container->getByType(Schemas\ModbusConnector::class, false));

		self::assertNotNull($container->getByType(Hydrators\ModbusDevice::class, false));
		self::assertNotNull($container->getByType(Hydrators\ModbusConnector::class, false));
	}

}
