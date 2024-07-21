<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Addon\VirtualThermostat\Commands;
use FastyBird\Addon\VirtualThermostat\Drivers;
use FastyBird\Addon\VirtualThermostat\Exceptions;
use FastyBird\Addon\VirtualThermostat\Helpers;
use FastyBird\Addon\VirtualThermostat\Hydrators;
use FastyBird\Addon\VirtualThermostat\Schemas;
use FastyBird\Addon\VirtualThermostat\Tests;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use Nette;
use RuntimeException;

final class VirtualThermostatExtensionTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->getContainer()->getByType(Schemas\Devices\Device::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Channels\Configuration::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Channels\Actors::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Channels\Sensors::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Channels\Preset::class, false));

		self::assertNotNull($this->getContainer()->getByType(Hydrators\Devices\Device::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Channels\Configuration::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Channels\Actors::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Channels\Sensors::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Channels\Preset::class, false));

		self::assertNotNull($this->getContainer()->getByType(Drivers\ThermostatFactory::class, false));

		self::assertNotNull($this->getContainer()->getByType(Helpers\Device::class, false));

		self::assertNotNull($this->getContainer()->getByType(Commands\Install::class, false));
	}

}
