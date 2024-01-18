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

final class FbMqttExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertNotNull($container->getByType(Writers\WriterFactory::class, false));

		self::assertNotNull($container->getByType(API\ConnectionManager::class, false));
		self::assertNotNull($container->getByType(API\ClientFactory::class, false));

		self::assertNotNull($container->getByType(Clients\FbMqttV1Factory::class, false));

		self::assertNotNull($container->getByType(Queue\Consumers\ChannelAttribute::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\ChannelProperty::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\DeviceAttribute::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\DeviceProperty::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\ExtensionAttribute::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\WriteV1PropertyState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers::class, false));
		self::assertNotNull($container->getByType(Queue\Queue::class, false));

		self::assertNotNull($container->getByType(Subscribers\Controls::class, false));

		self::assertNotNull($container->getByType(Schemas\FbMqttConnector::class, false));
		self::assertNotNull($container->getByType(Schemas\FbMqttDevice::class, false));

		self::assertNotNull($container->getByType(Hydrators\FbMqttConnector::class, false));
		self::assertNotNull($container->getByType(Hydrators\FbMqttDevice::class, false));

		self::assertNotNull($container->getByType(Helpers\Entity::class, false));
		self::assertNotNull($container->getByType(Helpers\Connector::class, false));

		self::assertNotNull($container->getByType(Commands\Execute::class, false));
		self::assertNotNull($container->getByType(Commands\Install::class, false));

		self::assertNotNull($container->getByType(Connector\ConnectorFactory::class, false));
	}

}
