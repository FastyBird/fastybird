<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostatDevice\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Addon\VirtualThermostatDevice\Commands;
use FastyBird\Addon\VirtualThermostatDevice\Drivers;
use FastyBird\Addon\VirtualThermostatDevice\Helpers;
use FastyBird\Addon\VirtualThermostatDevice\Hydrators;
use FastyBird\Addon\VirtualThermostatDevice\Schemas;
use FastyBird\Addon\VirtualThermostatDevice\Tests;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use Nette;

final class ThermostatDeviceExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertNotNull($container->getByType(Schemas\ThermostatDevice::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Configuration::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Actors::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Sensors::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Preset::class, false));

		self::assertNotNull($container->getByType(Hydrators\ThermostatDevice::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Configuration::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Actors::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Sensors::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Preset::class, false));

		self::assertNotNull($container->getByType(Drivers\ThermostatFactory::class, false));

		self::assertNotNull($container->getByType(Helpers\Device::class, false));

		self::assertNotNull($container->getByType(Commands\Install::class, false));
	}

}
