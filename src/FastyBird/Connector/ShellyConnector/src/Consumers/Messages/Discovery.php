<?php declare(strict_types = 1);

/**
 * Discovery.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Consumers
 * @since          0.37.0
 *
 * @date           20.07.22
 */

namespace FastyBird\ShellyConnector\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\DevicesModule\Entities as DevicesModuleEntities;
use FastyBird\DevicesModule\Exceptions as DevicesModuleExceptions;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\DevicesModule\Queries as DevicesModuleQueries;
use FastyBird\Metadata;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use FastyBird\ShellyConnector\Consumers\Consumer;
use FastyBird\ShellyConnector\Entities;
use FastyBird\ShellyConnector\Exceptions;
use FastyBird\ShellyConnector\Helpers;
use FastyBird\ShellyConnector\Types;
use Nette;
use Nette\Utils;
use Psr\Log;
use function assert;

/**
 * Device discovery message consumer
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Discovery implements Consumer
{

	use Nette\SmartObject;
	use TConsumeDeviceAttribute;
	use TConsumeDeviceProperty;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DevicesModuleModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModuleModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModuleModels\Devices\DevicesManager $devicesManager,
		private readonly DevicesModuleModels\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModuleModels\Devices\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesModuleModels\Devices\Attributes\AttributesRepository $attributesRepository,
		private readonly DevicesModuleModels\Devices\Attributes\AttributesManager $attributesManager,
		private readonly DevicesModuleModels\DataStorage\DevicesRepository $devicesDataStorageRepository,
		private readonly DevicesModuleModels\DataStorage\DevicePropertiesRepository $propertiesDataStorageRepository,
		private readonly DevicesModuleModels\DataStorage\DeviceAttributesRepository $attributesDataStorageRepository,
		private readonly Helpers\Database $databaseHelper,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesModuleExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\DeviceFound) {
			return false;
		}

		$deviceItem = $this->devicesDataStorageRepository->findByIdentifier(
			$entity->getConnector(),
			$entity->getIdentifier(),
		);

		if ($deviceItem === null) {
			$connectorEntity = $this->databaseHelper->query(
				function () use ($entity): DevicesModuleEntities\Connectors\Connector|null {
					$findConnectorQuery = new DevicesModuleQueries\FindConnectors();
					$findConnectorQuery->byId($entity->getConnector());

					return $this->connectorsRepository->findOneBy($findConnectorQuery);
				},
			);

			if ($connectorEntity === null) {
				return true;
			}

			$deviceEntity = $this->databaseHelper->transaction(
				function () use ($entity, $connectorEntity): Entities\ShellyDevice {
					$deviceEntity = $this->devicesManager->create(Utils\ArrayHash::from([
						'entity' => Entities\ShellyDevice::class,
						'connector' => $connectorEntity,
						'identifier' => $entity->getIdentifier(),
					]));
					assert($deviceEntity instanceof Entities\ShellyDevice);

					return $deviceEntity;
				},
			);

			$this->logger->info(
				'Creating new device',
				[
					'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
					'type' => 'discovery-message-consumer',
					'device' => [
						'id' => $deviceEntity->getPlainId(),
						'identifier' => $entity->getIdentifier(),
						'address' => $entity->getIpAddress(),
					],
				],
			);
		}

		$deviceItem = $this->devicesDataStorageRepository->findByIdentifier(
			$entity->getConnector(),
			$entity->getIdentifier(),
		);

		if ($deviceItem === null) {
			$this->logger->error(
				'Newly created device could not be loaded',
				[
					'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
					'type' => 'discovery-message-consumer',
					'device' => [
						'identifier' => $entity->getIdentifier(),
						'address' => $entity->getIpAddress(),
					],
				],
			);

			return true;
		}

		$this->setDeviceProperty(
			$deviceItem->getId(),
			$entity->getIpAddress(),
			Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
		);
		$this->setDeviceAttribute(
			$deviceItem->getId(),
			$entity->getType(),
			Types\DeviceAttributeIdentifier::IDENTIFIER_MODEL,
		);

		$this->logger->debug(
			'Consumed device found message',
			[
				'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
				'type' => 'discovery-message-consumer',
				'device' => [
					'id' => $deviceItem->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
