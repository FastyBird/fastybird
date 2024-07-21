<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Clients;
use FastyBird\Connector\Tuya\Commands;
use FastyBird\Connector\Tuya\Connector;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Hydrators;
use FastyBird\Connector\Tuya\Queue;
use FastyBird\Connector\Tuya\Schemas;
use FastyBird\Connector\Tuya\Services;
use FastyBird\Connector\Tuya\Subscribers;
use FastyBird\Connector\Tuya\Tests;
use FastyBird\Connector\Tuya\Writers;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use Nette;
use RuntimeException;

final class TuyaExtensionTest extends Tests\Cases\Unit\DbTestCase
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
		self::assertCount(2, $this->getContainer()->findByType(Writers\WriterFactory::class));

		self::assertNotNull($this->getContainer()->getByType(Clients\LocalFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Clients\CloudFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Clients\DiscoveryFactory::class, false));

		self::assertNotNull($this->getContainer()->getByType(Services\DatagramFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Services\HttpClientFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Services\SocketClientFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Services\WebSocketClientFactory::class, false));

		self::assertNotNull($this->getContainer()->getByType(API\ConnectionManager::class, false));
		self::assertNotNull($this->getContainer()->getByType(API\OpenApiFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(API\OpenPulsarFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(API\LocalApiFactory::class, false));

		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreCloudDevice::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreLocalDevice::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreDeviceConnectionState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreChannelPropertyState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\WriteChannelPropertyState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Queue::class, false));

		self::assertNotNull($this->getContainer()->getByType(Subscribers\Properties::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\Controls::class, false));

		self::assertNotNull($this->getContainer()->getByType(Schemas\Connectors\Connector::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Devices\Device::class, false));

		self::assertNotNull($this->getContainer()->getByType(Hydrators\Connectors\Connector::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Devices\Device::class, false));

		self::assertNotNull($this->getContainer()->getByType(Helpers\MessageBuilder::class, false));

		self::assertNotNull($this->getContainer()->getByType(Commands\Execute::class, false));
		self::assertNotNull($this->getContainer()->getByType(Commands\Discover::class, false));
		self::assertNotNull($this->getContainer()->getByType(Commands\Install::class, false));

		self::assertNotNull($this->getContainer()->getByType(Connector\ConnectorFactory::class, false));
	}

}
