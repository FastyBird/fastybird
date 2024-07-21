<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Connector\Zigbee2Mqtt\API;
use FastyBird\Connector\Zigbee2Mqtt\Clients;
use FastyBird\Connector\Zigbee2Mqtt\Commands;
use FastyBird\Connector\Zigbee2Mqtt\Connector;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Connector\Zigbee2Mqtt\Hydrators;
use FastyBird\Connector\Zigbee2Mqtt\Models;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Schemas;
use FastyBird\Connector\Zigbee2Mqtt\Subscribers;
use FastyBird\Connector\Zigbee2Mqtt\Tests;
use FastyBird\Connector\Zigbee2Mqtt\Writers;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use Nette;
use RuntimeException;

final class Zigbee2MqttExtensionTest extends Tests\Cases\Unit\DbTestCase
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

		self::assertNotNull($this->getContainer()->getByType(API\ConnectionManager::class, false));
		self::assertNotNull($this->getContainer()->getByType(API\ClientFactory::class, false));

		self::assertNotNull($this->getContainer()->getByType(Clients\MqttFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Clients\DiscoveryFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Clients\Subscribers\BridgeFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Clients\Subscribers\DeviceFactory::class, false));

		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Queue::class, false));

		self::assertNotNull($this->getContainer()->getByType(Subscribers\Properties::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\Controls::class, false));

		self::assertNotNull($this->getContainer()->getByType(Schemas\Connectors\Connector::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Devices\Bridge::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Devices\SubDevice::class, false));

		self::assertNotNull($this->getContainer()->getByType(Hydrators\Connectors\Connector::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Devices\Bridge::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Devices\SubDevice::class, false));

		self::assertNotNull($this->getContainer()->getByType(Models\StateRepository::class, false));

		self::assertNotNull($this->getContainer()->getByType(Helpers\MessageBuilder::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\Connectors\Connector::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\Devices\Bridge::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\Devices\SubDevice::class, false));

		self::assertNotNull($this->getContainer()->getByType(Commands\Execute::class, false));
		self::assertNotNull($this->getContainer()->getByType(Commands\Install::class, false));

		self::assertNotNull($this->getContainer()->getByType(Connector\ConnectorFactory::class, false));
	}

}
