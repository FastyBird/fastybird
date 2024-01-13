<?php declare(strict_types = 1);

namespace FastyBird\Addon\ThermostatDevice\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Addon\ThermostatDevice\Commands;
use FastyBird\Addon\ThermostatDevice\Drivers;
use FastyBird\Addon\ThermostatDevice\Helpers;
use FastyBird\Addon\ThermostatDevice\Hydrators;
use FastyBird\Addon\ThermostatDevice\Schemas;
use FastyBird\Addon\ThermostatDevice\Tests;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use Nette;

final class ThermostatDeviceExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
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
