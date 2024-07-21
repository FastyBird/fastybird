<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Builders;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Commands;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Controllers;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Hydrators;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Router;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Schemas;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Tests;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use Nette;

final class VirtualThermostatAddonHomeKitConnectorExtensionTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->getContainer()->getByType(Builders\Builder::class, false));

		self::assertNotNull($this->getContainer()->getByType(Commands\Build::class, false));

		self::assertNotNull($this->getContainer()->getByType(Controllers\BridgesV1::class, false));

		self::assertNotNull($this->getContainer()->getByType(Schemas\Channels\Thermostat::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Devices\Thermostat::class, false));

		self::assertNotNull($this->getContainer()->getByType(Hydrators\Channels\Thermostat::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Devices\Thermostat::class, false));

		self::assertNotNull($this->getContainer()->getByType(Router\ApiRoutes::class, false));
	}

}
