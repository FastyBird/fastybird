<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Clients;
use FastyBird\Connector\NsPanel\Commands;
use FastyBird\Connector\NsPanel\Connector;
use FastyBird\Connector\NsPanel\Controllers;
use FastyBird\Connector\NsPanel\Exceptions;
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
use RuntimeException;

final class NsPanelExtensionTest extends Tests\Cases\Unit\DbTestCase
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

		self::assertNotNull($this->getContainer()->getByType(Clients\GatewayFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Clients\DeviceFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Clients\DiscoveryFactory::class, false));

		self::assertNotNull($this->getContainer()->getByType(Services\HttpClientFactory::class, false));

		self::assertNotNull($this->getContainer()->getByType(API\LanApiFactory::class, false));

		self::assertNotNull($this->getContainer()->getByType(Servers\HttpFactory::class, false));

		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreDeviceState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreDeviceConnectionState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreThirdPartyDevice::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreSubDevice::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\WriteSubDeviceState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Queue::class, false));

		self::assertNotNull($this->getContainer()->getByType(Subscribers\Properties::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\Controls::class, false));

		self::assertNotNull($this->getContainer()->getByType(Schemas\Connectors\Connector::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Devices\Gateway::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Devices\SubDevice::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Devices\ThirdPartyDevice::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Channels\Channel::class, false));

		self::assertNotNull($this->getContainer()->getByType(Hydrators\Connectors\Connector::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Devices\Gateway::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Devices\SubDevice::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Devices\ThirdPartyDevice::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Channels\Channel::class, false));

		self::assertNotNull($this->getContainer()->getByType(Models\StateRepository::class, false));

		self::assertNotNull($this->getContainer()->getByType(Helpers\Loader::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\MessageBuilder::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\Connectors\Connector::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\Devices\Gateway::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\Devices\ThirdPartyDevice::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\Devices\SubDevice::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\Channels\Channel::class, false));

		self::assertNotNull($this->getContainer()->getByType(Middleware\Router::class, false));

		self::assertNotNull($this->getContainer()->getByType(Controllers\DirectiveController::class, false));

		self::assertNotNull($this->getContainer()->getByType(Commands\Execute::class, false));
		self::assertNotNull($this->getContainer()->getByType(Commands\Discover::class, false));
		self::assertNotNull($this->getContainer()->getByType(Commands\Install::class, false));

		self::assertNotNull($this->getContainer()->getByType(Connector\ConnectorFactory::class, false));
	}

}
