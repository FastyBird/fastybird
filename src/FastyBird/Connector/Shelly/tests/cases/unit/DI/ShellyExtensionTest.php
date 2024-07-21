<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Clients;
use FastyBird\Connector\Shelly\Commands;
use FastyBird\Connector\Shelly\Connector;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Hydrators;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Connector\Shelly\Schemas;
use FastyBird\Connector\Shelly\Services;
use FastyBird\Connector\Shelly\Subscribers;
use FastyBird\Connector\Shelly\Tests;
use FastyBird\Connector\Shelly\Writers;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use Nette;
use RuntimeException;

final class ShellyExtensionTest extends Tests\Cases\Unit\DbTestCase
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

		self::assertNotNull($this->getContainer()->getByType(Services\HttpClientFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Services\MulticastFactory::class, false));

		self::assertNotNull($this->getContainer()->getByType(API\ConnectionManager::class, false));
		self::assertNotNull($this->getContainer()->getByType(API\Gen1CoapFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(API\Gen1HttpApiFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(API\Gen2HttpApiFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(API\Gen2WsApiFactory::class, false));

		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreLocalDevice::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreDeviceConnectionState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreDeviceState::class, false));
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
		self::assertNotNull($this->getContainer()->getByType(Helpers\Connector::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\Device::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\Loader::class, false));

		self::assertNotNull($this->getContainer()->getByType(Commands\Execute::class, false));
		self::assertNotNull($this->getContainer()->getByType(Commands\Discover::class, false));
		self::assertNotNull($this->getContainer()->getByType(Commands\Install::class, false));

		self::assertNotNull($this->getContainer()->getByType(Connector\ConnectorFactory::class, false));
	}

}
