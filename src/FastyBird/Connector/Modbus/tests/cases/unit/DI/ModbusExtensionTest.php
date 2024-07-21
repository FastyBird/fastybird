<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Connector\Modbus\API;
use FastyBird\Connector\Modbus\Clients;
use FastyBird\Connector\Modbus\Commands;
use FastyBird\Connector\Modbus\Connector;
use FastyBird\Connector\Modbus\Exceptions;
use FastyBird\Connector\Modbus\Helpers;
use FastyBird\Connector\Modbus\Hydrators;
use FastyBird\Connector\Modbus\Queue;
use FastyBird\Connector\Modbus\Schemas;
use FastyBird\Connector\Modbus\Subscribers;
use FastyBird\Connector\Modbus\Tests;
use FastyBird\Connector\Modbus\Writers;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use Nette;
use RuntimeException;

final class ModbusExtensionTest extends Tests\Cases\Unit\DbTestCase
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

		self::assertNotNull($this->getContainer()->getByType(Clients\RtuFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Clients\TcpFactory::class, false));

		self::assertNotNull($this->getContainer()->getByType(API\RtuFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(API\TcpFactory::class, false));
		self::assertNotNull($this->getContainer()->getByType(API\Transformer::class, false));

		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreDeviceConnectionState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\StoreChannelPropertyState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers\WriteChannelPropertyState::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Consumers::class, false));
		self::assertNotNull($this->getContainer()->getByType(Queue\Queue::class, false));

		self::assertNotNull($this->getContainer()->getByType(Subscribers\Properties::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\Controls::class, false));

		self::assertNotNull($this->getContainer()->getByType(Schemas\Connectors\Connector::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Devices\Device::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Channels\Channel::class, false));

		self::assertNotNull($this->getContainer()->getByType(Hydrators\Connectors\Connector::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Devices\Device::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Channels\Channel::class, false));

		self::assertNotNull($this->getContainer()->getByType(Helpers\MessageBuilder::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\Connector::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\Device::class, false));
		self::assertNotNull($this->getContainer()->getByType(Helpers\Channel::class, false));

		self::assertNotNull($this->getContainer()->getByType(Commands\Execute::class, false));
		self::assertNotNull($this->getContainer()->getByType(Commands\Install::class, false));

		self::assertNotNull($this->getContainer()->getByType(Connector\ConnectorFactory::class, false));
	}

}
