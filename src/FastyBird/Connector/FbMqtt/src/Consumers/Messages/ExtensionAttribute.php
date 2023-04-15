<?php declare(strict_types = 1);

/**
 * ExtensionAttribute.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           05.07.22
 */

namespace FastyBird\Connector\FbMqtt\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\Connector\FbMqtt\Consumers;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Types;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use Psr\Log;
use function sprintf;

/**
 * Device extension MQTT message consumer
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ExtensionAttribute implements Consumers\Consumer
{

	use Nette\SmartObject;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DevicesModels\Devices\DevicesRepository $deviceRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesUtilities\Database $databaseHelper,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\ExtensionAttribute) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getDevice());

		$device = $this->deviceRepository->findOneBy($findDeviceQuery, Entities\FbMqttDevice::class);

		if ($device === null) {
			$this->logger->error(
				sprintf('Device "%s" is not registered', $entity->getDevice()),
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
					'type' => 'extension-attribute-message-consumer',
					'device' => [
						'identifier' => $entity->getDevice(),
					],
				],
			);

			return true;
		}

		$propertyIdentifier = null;

		// HARDWARE INFO
		if (
			$entity->getExtension()->equalsValue(Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_HARDWARE)
			&& $entity->getParameter() === Entities\Messages\ExtensionAttribute::MANUFACTURER
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MANUFACTURER;

		} elseif (
			$entity->getExtension()->equalsValue(Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_HARDWARE)
			&& $entity->getParameter() === Entities\Messages\ExtensionAttribute::MODEL
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MODEL;

		} elseif (
			$entity->getExtension()->equalsValue(Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_HARDWARE)
			&& $entity->getParameter() === Entities\Messages\ExtensionAttribute::VERSION
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_VERSION;

		} elseif (
			$entity->getExtension()->equalsValue(Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_HARDWARE)
			&& $entity->getParameter() === Entities\Messages\ExtensionAttribute::MAC_ADDRESS
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS;

			// FIRMWARE INFO
		} elseif (
			$entity->getExtension()->equalsValue(Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_FIRMWARE)
			&& $entity->getParameter() === Entities\Messages\ExtensionAttribute::MANUFACTURER
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_MANUFACTURER;

		} elseif (
			$entity->getExtension()->equalsValue(Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_FIRMWARE)
			&& $entity->getParameter() === Entities\Messages\ExtensionAttribute::NAME
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_NAME;

		} elseif (
			$entity->getExtension()->equalsValue(Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_FIRMWARE)
			&& $entity->getParameter() === Entities\Messages\ExtensionAttribute::VERSION
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_VERSION;
		}

		if ($propertyIdentifier === null) {
			return true;
		}

		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier($propertyIdentifier);

		$property = $this->propertiesRepository->findOneBy($findDevicePropertyQuery);

		if ($property === null) {
			$this->logger->error(
				sprintf('Device property "%s" is not registered', $entity->getParameter()),
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
					'type' => 'extension-attribute-message-consumer',
					'device' => [
						'identifier' => $entity->getDevice(),
					],
					'property' => [
						'identifier' => $entity->getParameter(),
					],
				],
			);

			return true;
		}

		$this->databaseHelper->transaction(function () use ($entity, $property): void {
			$toUpdate = [
				'value' => $entity->getValue(),
			];

			$this->propertiesManager->update($property, Utils\ArrayHash::from($toUpdate));
		});

		$this->logger->debug(
			'Consumed extension property message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
				'type' => 'extension-attribute-message-consumer',
				'device' => [
					'id' => $device->getPlainId(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
