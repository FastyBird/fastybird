<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Connector\Virtual\Commands;
use FastyBird\Connector\Virtual\Connector;
use FastyBird\Connector\Virtual\Devices;
use FastyBird\Connector\Virtual\Drivers;
use FastyBird\Connector\Virtual\Helpers;
use FastyBird\Connector\Virtual\Hydrators;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\Connector\Virtual\Schemas;
use FastyBird\Connector\Virtual\Subscribers;
use FastyBird\Connector\Virtual\Tests;
use FastyBird\Connector\Virtual\Writers;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use Nette;

final class VirtualExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertNotNull($container->getByType(Writers\WriterFactory::class, false));

		self::assertNotNull($container->getByType(Queue\Consumers\StoreDeviceConnectionState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\StoreDevicePropertyState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\StoreChannelPropertyState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\WriteDevicePropertyState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\WriteChannelPropertyState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers::class, false));
		self::assertNotNull($container->getByType(Queue\Queue::class, false));

		self::assertNotNull($container->getByType(Subscribers\Properties::class, false));
		self::assertNotNull($container->getByType(Subscribers\Controls::class, false));

		self::assertNotNull($container->getByType(Schemas\VirtualConnector::class, false));
		self::assertNotNull($container->getByType(Schemas\Devices\Thermostat::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Thermostat::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Actors::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Sensors::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Preset::class, false));

		self::assertNotNull($container->getByType(Hydrators\VirtualConnector::class, false));
		self::assertNotNull($container->getByType(Hydrators\Devices\Thermostat::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Thermostat::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Actors::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Sensors::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Preset::class, false));

		self::assertNotNull($container->getByType(Devices\DevicesFactory::class, false));

		self::assertNotNull($container->getByType(Drivers\DriversManager::class, false));
		self::assertNotNull($container->getByType(Drivers\ThermostatFactory::class, false));

		self::assertNotNull($container->getByType(Helpers\Entity::class, false));

		self::assertNotNull($container->getByType(Commands\Execute::class, false));
		self::assertNotNull($container->getByType(Commands\Install::class, false));

		self::assertNotNull($container->getByType(Connector\ConnectorFactory::class, false));
	}

}
