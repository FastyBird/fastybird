<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Connector\FbMqtt\API;
use FastyBird\Connector\FbMqtt\Clients;
use FastyBird\Connector\FbMqtt\Commands;
use FastyBird\Connector\FbMqtt\Connector;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Connector\FbMqtt\Hydrators;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\Connector\FbMqtt\Schemas;
use FastyBird\Connector\FbMqtt\Subscribers;
use FastyBird\Connector\FbMqtt\Tests;
use FastyBird\Connector\FbMqtt\Writers;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use Nette;

final class FbMqttExtensionTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		self::assertCount(2, $this->getContainer()->findByType(Writers\WriterFactory::class));

		self::assertNotNull($this->getContainer()->getByType(API\ConnectionManager::class, false));
		self::assertNotNull($this->getContainer()->getByType(API\ClientFactory::class, false));

		self::assertNotNull($this->getContainer()->getByType(Clients\FbMqttV1Factory::class, false));

		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\ChannelAttribute::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\ChannelProperty::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\DeviceAttribute::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\DeviceProperty::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\ExtensionAttribute::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\WriteV1DevicePropertyState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\WriteV1ChannelPropertyState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Queue::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\MessageBuilder::class, false));

		self::assertNotNull($this->getContainer()->getByType(Subscribers\Controls::class, false));

		self::assertNotNull($this->getContainer()->getByType(Schemas\Connectors\Connector::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Devices\Device::class, false));

		self::assertNotNull($this->getContainer()->getByType(Hydrators\Connectors\Connector::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Devices\Device::class, false));

		self::assertNotNull($this->getContainer()->getByType(Helpers\Connector::class, false));

		self::assertNotNull($this->getContainer()->getByType(Commands\Execute::class, false));
		self::assertNotNull($this->getContainer()->getByType(Commands\Install::class, false));

		self::assertNotNull($this->getContainer()->getByType(Connector\ConnectorFactory::class, false));
	}

}
