<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Clients;
use FastyBird\Connector\NsPanel\Commands;
use FastyBird\Connector\NsPanel\Connector;
use FastyBird\Connector\NsPanel\Controllers;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Hydrators;
use FastyBird\Connector\NsPanel\Middleware;
use FastyBird\Connector\NsPanel\Models;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Schemas;
use FastyBird\Connector\NsPanel\Servers;
use FastyBird\Connector\NsPanel\Services;
use FastyBird\Connector\NsPanel\Subscribers;
use FastyBird\Connector\NsPanel\Tests;
use FastyBird\Connector\NsPanel\Writers;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use Nette;

final class NsPanelExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertCount(2, $container->findByType(Writers\WriterFactory::class));

		self::assertNotNull($container->getByType(Clients\GatewayFactory::class, false));
		self::assertNotNull($container->getByType(Clients\DeviceFactory::class, false));
		self::assertNotNull($container->getByType(Clients\DiscoveryFactory::class, false));

		self::assertNotNull($container->getByType(Services\HttpClientFactory::class, false));

		self::assertNotNull($container->getByType(API\LanApiFactory::class, false));

		self::assertNotNull($container->getByType(Servers\HttpFactory::class, false));

		self::assertNotNull($container->getByType(Queue\Consumers\StoreDeviceState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\StoreDeviceConnectionState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\StoreThirdPartyDevice::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\StoreSubDevice::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\WriteSubDeviceState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers::class, false));
		self::assertNotNull($container->getByType(Queue\Queue::class, false));

		self::assertNotNull($container->getByType(Subscribers\Properties::class, false));
		self::assertNotNull($container->getByType(Subscribers\Controls::class, false));

		self::assertNotNull($container->getByType(Schemas\Connectors\Connector::class, false));
		self::assertNotNull($container->getByType(Schemas\Devices\Gateway::class, false));
		self::assertNotNull($container->getByType(Schemas\Devices\SubDevice::class, false));
		self::assertNotNull($container->getByType(Schemas\Devices\ThirdPartyDevice::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Channel::class, false));

		self::assertNotNull($container->getByType(Hydrators\Connectors\Connector::class, false));
		self::assertNotNull($container->getByType(Hydrators\Devices\Gateway::class, false));
		self::assertNotNull($container->getByType(Hydrators\Devices\SubDevice::class, false));
		self::assertNotNull($container->getByType(Hydrators\Devices\ThirdPartyDevice::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Channel::class, false));

		self::assertNotNull($container->getByType(Models\StateRepository::class, false));

		self::assertNotNull($container->getByType(Helpers\Loader::class, false));
		self::assertNotNull($container->getByType(Helpers\MessageBuilder::class, false));
		self::assertNotNull($container->getByType(Helpers\Connectors\Connector::class, false));
		self::assertNotNull($container->getByType(Helpers\Devices\Gateway::class, false));
		self::assertNotNull($container->getByType(Helpers\Devices\ThirdPartyDevice::class, false));
		self::assertNotNull($container->getByType(Helpers\Devices\SubDevice::class, false));
		self::assertNotNull($container->getByType(Helpers\Channels\Channel::class, false));

		self::assertNotNull($container->getByType(Middleware\Router::class, false));

		self::assertNotNull($container->getByType(Controllers\DirectiveController::class, false));

		self::assertNotNull($container->getByType(Commands\Execute::class, false));
		self::assertNotNull($container->getByType(Commands\Discover::class, false));
		self::assertNotNull($container->getByType(Commands\Install::class, false));

		self::assertNotNull($container->getByType(Connector\ConnectorFactory::class, false));
	}

}
