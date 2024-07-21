<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Connector\Virtual\Commands;
use FastyBird\Connector\Virtual\Connector;
use FastyBird\Connector\Virtual\Devices;
use FastyBird\Connector\Virtual\Drivers;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Helpers;
use FastyBird\Connector\Virtual\Hydrators;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\Connector\Virtual\Schemas;
use FastyBird\Connector\Virtual\Subscribers;
use FastyBird\Connector\Virtual\Tests;
use FastyBird\Connector\Virtual\Writers;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use Nette;
use RuntimeException;

final class VirtualExtensionTest extends Tests\Cases\Unit\DbTestCase
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

		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreDeviceConnectionState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreDevicePropertyState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreChannelPropertyState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\WriteDevicePropertyState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\WriteChannelPropertyState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Queue::class, false));

		self::assertNotNull($this->getContainer()->getByType(Subscribers\Properties::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\Controls::class, false));

		self::assertNotNull($this->getContainer()->getByType(Schemas\Connectors\Connector::class, false));

		self::assertNotNull($this->getContainer()->getByType(Hydrators\Connectors\Connector::class, false));

		self::assertNotNull($this->getContainer()->getByType(Devices\DevicesFactory::class, false));

		self::assertNotNull($this->getContainer()->getByType(Drivers\DriversManager::class, false));

		self::assertNotNull($this->getContainer()->getByType(Helpers\MessageBuilder::class, false));

		self::assertNotNull($this->getContainer()->getByType(Commands\Execute::class, false));
		self::assertNotNull($this->getContainer()->getByType(Commands\Install::class, false));

		self::assertNotNull($this->getContainer()->getByType(Connector\ConnectorFactory::class, false));
	}

}
