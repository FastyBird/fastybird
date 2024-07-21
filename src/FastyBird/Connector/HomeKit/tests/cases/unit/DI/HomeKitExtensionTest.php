<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Connector\HomeKit\Clients;
use FastyBird\Connector\HomeKit\Commands;
use FastyBird\Connector\HomeKit\Connector;
use FastyBird\Connector\HomeKit\Controllers;
use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Helpers;
use FastyBird\Connector\HomeKit\Hydrators;
use FastyBird\Connector\HomeKit\Middleware;
use FastyBird\Connector\HomeKit\Models;
use FastyBird\Connector\HomeKit\Protocol;
use FastyBird\Connector\HomeKit\Queue;
use FastyBird\Connector\HomeKit\Schemas;
use FastyBird\Connector\HomeKit\Servers;
use FastyBird\Connector\HomeKit\Subscribers;
use FastyBird\Connector\HomeKit\Tests;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use Nette;
use RuntimeException;
use function in_array;

final class HomeKitExtensionTest extends Tests\Cases\Unit\DbTestCase
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
		self::assertNotNull($this->getContainer()->getByType(Servers\MdnsFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Servers\HttpFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Servers\SecureServerFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Servers\SecureConnectionFactory::class, false));

		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreDeviceConnectionState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreDevicePropertyState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreChannelPropertyState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\WriteDevicePropertyState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\WriteChannelPropertyState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Queue::class, false));

		self::assertNotNull($this->getContainer()->getByType(Subscribers\Properties::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\Controls::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\System::class, false));

		self::assertNotNull($this->getContainer()->getByType(Schemas\Connectors\Connector::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Devices\Device::class, false));
		foreach ($this->getContainer()->findByType(Schemas\Channels\Channel::class) as $serviceName) {
			$service = $this->getContainer()->getByName($serviceName);

			self::assertTrue(in_array(
				$service::class,
				[
					Schemas\Channels\Generic::class,
					Schemas\Channels\Battery::class,
					Schemas\Channels\LightBulb::class,
				],
				true,
			));
		}

		self::assertNotNull($this->getContainer()->getByType(Hydrators\Connectors\Connector::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Devices\Device::class, false));
		foreach ($this->getContainer()->findByType(Hydrators\Channels\Channel::class) as $serviceName) {
			$service = $this->getContainer()->getByName($serviceName);

			self::assertTrue(in_array(
				$service::class,
				[
					Hydrators\Channels\Generic::class,
					Hydrators\Channels\Battery::class,
					Hydrators\Channels\LightBulb::class,
				],
				true,
			));
		}

		self::assertNotNull($this->getContainer()->getByType(Helpers\MessageBuilder::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\Loader::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\Connector::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\Device::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\Channel::class, false));

		self::assertNotNull($this->getContainer()->getByType(Middleware\Router::class, false));

		self::assertNotNull($this->getContainer()->getByType(Controllers\AccessoriesController::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\CharacteristicsController::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\PairingController::class, false));

		self::assertNotNull($this->getContainer()->getByType(Protocol\Accessories\BridgeFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Protocol\Accessories\GenericFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Protocol\Services\GenericFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Protocol\Services\LightBulbFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Protocol\Services\BatteryFactory::class, false));
		self::assertNotNull(
			$this->getContainer()->getByType(Protocol\Characteristics\DynamicPropertyFactory::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Protocol\Characteristics\MappedPropertyFactory::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Protocol\Characteristics\VariablePropertyFactory::class, false),
		);

		self::assertNotNull($this->getContainer()->getByType(Protocol\Tlv::class, false));
		self::assertNotNull($this->getContainer()->getByType(Protocol\Driver::class, false));
		self::assertNotNull($this->getContainer()->getByType(Clients\Subscriber::class, false));

		self::assertNotNull($this->getContainer()->getByType(Models\Entities\Clients\ClientsRepository::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\Entities\Clients\ClientsManager::class, false));

		self::assertNotNull($this->getContainer()->getByType(Commands\Execute::class, false));
		self::assertNotNull($this->getContainer()->getByType(Commands\Install::class, false));

		self::assertNotNull($this->getContainer()->getByType(Connector\ConnectorFactory::class, false));
	}

}
