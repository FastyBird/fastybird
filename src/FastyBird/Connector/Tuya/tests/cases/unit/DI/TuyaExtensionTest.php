<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Clients;
use FastyBird\Connector\Tuya\Commands;
use FastyBird\Connector\Tuya\Connector;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Hydrators;
use FastyBird\Connector\Tuya\Queue;
use FastyBird\Connector\Tuya\Schemas;
use FastyBird\Connector\Tuya\Services;
use FastyBird\Connector\Tuya\Subscribers;
use FastyBird\Connector\Tuya\Tests;
use FastyBird\Connector\Tuya\Writers;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use Nette;

final class TuyaExtensionTest extends Tests\Cases\Unit\BaseTestCase
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

		self::assertNotNull($container->getByType(Clients\LocalFactory::class, false));
		self::assertNotNull($container->getByType(Clients\CloudFactory::class, false));
		self::assertNotNull($container->getByType(Clients\DiscoveryFactory::class, false));

		self::assertNotNull($container->getByType(Services\DatagramFactory::class, false));
		self::assertNotNull($container->getByType(Services\HttpClientFactory::class, false));
		self::assertNotNull($container->getByType(Services\SocketClientFactory::class, false));
		self::assertNotNull($container->getByType(Services\WebSocketClientFactory::class, false));

		self::assertNotNull($container->getByType(API\ConnectionManager::class, false));
		self::assertNotNull($container->getByType(API\OpenApiFactory::class, false));
		self::assertNotNull($container->getByType(API\OpenPulsarFactory::class, false));
		self::assertNotNull($container->getByType(API\LocalApiFactory::class, false));

		self::assertNotNull($container->getByType(Queue\Consumers\StoreCloudDevice::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\StoreLocalDevice::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\StoreDeviceConnectionState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\StoreChannelPropertyState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\WriteChannelPropertyState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers::class, false));
		self::assertNotNull($container->getByType(Queue\Queue::class, false));

		self::assertNotNull($container->getByType(Subscribers\Properties::class, false));
		self::assertNotNull($container->getByType(Subscribers\Controls::class, false));

		self::assertNotNull($container->getByType(Schemas\TuyaConnector::class, false));
		self::assertNotNull($container->getByType(Schemas\TuyaDevice::class, false));

		self::assertNotNull($container->getByType(Hydrators\TuyaConnector::class, false));
		self::assertNotNull($container->getByType(Hydrators\TuyaDevice::class, false));

		self::assertNotNull($container->getByType(Helpers\Entity::class, false));

		self::assertNotNull($container->getByType(Commands\Initialize::class, false));
		self::assertNotNull($container->getByType(Commands\Execute::class, false));
		self::assertNotNull($container->getByType(Commands\Discovery::class, false));

		self::assertNotNull($container->getByType(Connector\ConnectorFactory::class, false));
	}

}
