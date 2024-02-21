<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Tests\Cases\Unit\Builders;

use Error;
use FastyBird\Addon\VirtualThermostat\Entities as VirtualThermostatEntities;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Builders;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Exceptions;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Tests\Cases\Unit\DbTestCase;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette\DI;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class BuilderTest extends DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DI\MissingServiceException
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Error
	 * @throws RuntimeException
	 */
	public function testBuild(): void
	{
		$builder = $this->getContainer()->getByType(Builders\Builder::class);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$devicesRepository = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class);

		$findConnectorQuery = new DevicesQueries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('homekit');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery);

		$findThermostatQuery = new DevicesQueries\Entities\FindDevices();
		$findThermostatQuery->byIdentifier('thermostat-office');

		$thermostat = $devicesRepository->findOneBy($findThermostatQuery);

		self::assertInstanceOf(HomeKitEntities\Connectors\Connector::class, $connector);
		self::assertInstanceOf(VirtualThermostatEntities\Devices\Device::class, $thermostat);

		$bridge = $builder->build($thermostat, $connector);

		self::assertSame($thermostat, $bridge->getParent());

		self::assertCount(1, $bridge->getChannels());
		self::assertInstanceOf(Entities\Channels\Thermostat::class, $bridge->getChannels()[0]);
	}

}
