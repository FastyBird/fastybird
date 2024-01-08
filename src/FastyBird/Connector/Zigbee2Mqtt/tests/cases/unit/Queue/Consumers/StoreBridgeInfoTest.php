<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Tests\Cases\Unit\Queue\Consumers;

use Doctrine\DBAL;
use Error;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Tests\Cases\Unit\DbTestCase;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use Ramsey\Uuid;
use RuntimeException;
use function assert;

final class StoreBridgeInfoTest extends DbTestCase
{

	/**
	 * @throws DBAL\Exception
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testConsumeEntity(): void
	{
		$consumer = $this->getContainer()->getByType(
			Queue\Consumers\StoreBridgeInfo::class,
		);

		$entityFactory = $this->getContainer()->getByType(
			Helpers\Entity::class,
		);

		$entity = $entityFactory->create(
			Entities\Messages\StoreBridgeInfo::class,
			[
				'connector' => Uuid\Uuid::fromString('f15d2072-fb60-421a-a85f-2566e4dc13fe'),
				'base_topic' => 'zigbee2mqtt',
				'version' => '1.2.3',
				'commit' => '56589dc',
				'coordinator' => [
					'ieee_address' => '0xa4c138f06eafa3da',
					'type' => 'Coordinator',
				],
			],
		);

		$consumer->consume($entity);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$connector = $connectorsRepository->find(
			Uuid\Uuid::fromString('f15d2072-fb60-421a-a85f-2566e4dc13fe'),
			Entities\Zigbee2MqttConnector::class,
		);
		assert($connector instanceof Entities\Zigbee2MqttConnector);

		$devicesRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Devices\DevicesRepository::class,
		);

		$findDevicesQuery = new Queries\Entities\FindBridgeDevices();
		$findDevicesQuery->forConnector($connector);

		$bridge = $devicesRepository->findOneBy($findDevicesQuery, Entities\Devices\Bridge::class);
		assert($bridge instanceof Entities\Devices\Bridge);

		self::assertSame('1.2.3', $bridge->getFirmwareVersion());
		self::assertSame('56589dc', $bridge->getFirmwareCommit());
		self::assertSame('0xa4c138f06eafa3da', $bridge->getIeeeAddress());
	}

}
