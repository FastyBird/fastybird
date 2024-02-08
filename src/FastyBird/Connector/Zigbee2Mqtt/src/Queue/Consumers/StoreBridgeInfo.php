<?php declare(strict_types = 1);

/**
 * StoreBridgeInfo.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           01.01.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;

/**
 * Store bridge info message consumer
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreBridgeInfo implements Queue\Consumer
{

	use DeviceProperty;
	use Nette\SmartObject;

	public function __construct(
		protected readonly Zigbee2Mqtt\Logger $logger,
		protected readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		protected readonly ApplicationHelpers\Database $databaseHelper,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\StoreBridgeInfo) {
			return false;
		}

		$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceVariableProperties();
		$findDevicePropertyQuery->byIdentifier(Zigbee2Mqtt\Types\DevicePropertyIdentifier::BASE_TOPIC);
		$findDevicePropertyQuery->byValue($entity->getBaseTopic());

		$baseTopicProperty = $this->devicesPropertiesRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesEntities\Devices\Properties\Variable::class,
		);

		if ($baseTopicProperty === null) {
			return true;
		}

		$bridge = $this->devicesRepository->find(
			$baseTopicProperty->getDevice()->getId(),
			Entities\Devices\Bridge::class,
		);

		if ($bridge === null) {
			return true;
		}

		$this->databaseHelper->transaction(
			function () use ($bridge, $entity): void {
				$this->devicesManager->update(
					$bridge,
					Utils\ArrayHash::from([
						'identifier' => $entity->getCoordinator()->getIeeeAddress(),
					]),
				);
			},
		);

		$this->setDeviceProperty(
			$bridge->getId(),
			$entity->getCoordinator()->getType(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::MODEL,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MODEL),
		);
		$this->setDeviceProperty(
			$bridge->getId(),
			Types\DeviceType::COORDINATOR,
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::TYPE,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::TYPE),
		);
		$this->setDeviceProperty(
			$bridge->getId(),
			$entity->getVersion(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::VERSION,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::VERSION),
		);
		$this->setDeviceProperty(
			$bridge->getId(),
			$entity->getCommit(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::COMMIT,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::COMMIT),
		);
		$this->setDeviceProperty(
			$bridge->getId(),
			$entity->getCoordinator()->getIeeeAddress(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::IEEE_ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::IEEE_ADDRESS),
		);

		$this->logger->debug(
			'Consumed bridge info message',
			[
				'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
				'type' => 'store-bridge-info-message-consumer',
				'connector' => [
					'id' => $entity->getConnector()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
