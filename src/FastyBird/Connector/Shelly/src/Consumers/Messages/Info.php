<?php declare(strict_types = 1);

/**
 * Info.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Consumers
 * @since          0.37.0
 *
 * @date           20.07.22
 */

namespace FastyBird\Connector\Shelly\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\Connector\Shelly\Consumers\Consumer;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use Nette\Utils;
use Psr\Log;
use function assert;

/**
 * Device description message consumer
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Info implements Consumer
{

	use Nette\SmartObject;
	use TConsumeDeviceAttribute;
	use TConsumeDeviceProperty;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesModels\Devices\Attributes\AttributesRepository $attributesRepository,
		private readonly DevicesModels\Devices\Attributes\AttributesManager $attributesManager,
		private readonly DevicesModels\DataStorage\DevicesRepository $devicesDataStorageRepository,
		private readonly DevicesModels\DataStorage\DevicePropertiesRepository $propertiesDataStorageRepository,
		private readonly DevicesModels\DataStorage\DeviceAttributesRepository $attributesDataStorageRepository,
		private readonly Helpers\Database $databaseHelper,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
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
		if (!$entity instanceof Entities\Messages\DeviceInfo) {
			return false;
		}

		$deviceItem = $this->devicesDataStorageRepository->findByIdentifier(
			$entity->getConnector(),
			$entity->getIdentifier(),
		);

		if ($deviceItem === null) {
			return true;
		}

		if ($deviceItem->getName() === null && $deviceItem->getName() !== $entity->getType()) {
			$deviceEntity = $this->databaseHelper->query(
				function () use ($deviceItem): Entities\ShellyDevice|null {
					$findDeviceQuery = new DevicesQueries\FindDevices();
					$findDeviceQuery->byId($deviceItem->getId());

					$deviceEntity = $this->devicesRepository->findOneBy($findDeviceQuery);
					assert($deviceEntity instanceof Entities\ShellyDevice || $deviceEntity === null);

					return $deviceEntity;
				},
			);

			if ($deviceEntity === null) {
				return true;
			}

			$this->databaseHelper->transaction(function () use ($entity, $deviceEntity): void {
				$this->devicesManager->update($deviceEntity, Utils\ArrayHash::from([
					'name' => $entity->getType(),
				]));
			});
		}

		$this->setDeviceProperty(
			$deviceItem->getId(),
			$entity->getIpAddress(),
			Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
		);
		$this->setDeviceProperty(
			$deviceItem->getId(),
			$entity->isAuthEnabled(),
			Types\DevicePropertyIdentifier::IDENTIFIER_AUTH_ENABLED,
		);
		$this->setDeviceAttribute(
			$deviceItem->getId(),
			$entity->getType(),
			Types\DeviceAttributeIdentifier::IDENTIFIER_MODEL,
		);
		$this->setDeviceAttribute(
			$deviceItem->getId(),
			$entity->getMacAddress(),
			Types\DeviceAttributeIdentifier::IDENTIFIER_MAC_ADDRESS,
		);
		$this->setDeviceAttribute(
			$deviceItem->getId(),
			$entity->getFirmwareVersion(),
			Types\DeviceAttributeIdentifier::IDENTIFIER_FIRMWARE_VERSION,
		);

		$this->logger->debug(
			'Consumed device info message',
			[
				'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
				'type' => 'info-message-consumer',
				'device' => [
					'id' => $deviceItem->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
