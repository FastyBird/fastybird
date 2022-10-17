<?php declare(strict_types = 1);

/**
 * ExtensionAttribute.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 * @since          0.25.0
 *
 * @date           05.07.22
 */

namespace FastyBird\Connector\FbMqtt\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\Connector\FbMqtt\Consumers;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Connector\FbMqtt\Types;
use FastyBird\DevicesModule\Entities as DevicesModuleEntities;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\DevicesModule\Queries as DevicesModuleQueries;
use FastyBird\Metadata;
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
		private readonly DevicesModuleModels\Devices\DevicesRepository $deviceRepository,
		private readonly DevicesModuleModels\Devices\Attributes\AttributesManager $attributesManager,
		private readonly Helpers\Database $databaseHelper,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\ExtensionAttribute) {
			return false;
		}

		$device = $this->databaseHelper->query(function () use ($entity): DevicesModuleEntities\Devices\Device|null {
			$findDeviceQuery = new DevicesModuleQueries\FindDevices();
			$findDeviceQuery->byIdentifier($entity->getDevice());

			return $this->deviceRepository->findOneBy($findDeviceQuery);
		});

		if ($device === null) {
			$this->logger->error(
				sprintf('Device "%s" is not registered', $entity->getDevice()),
				[
					'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
					'type' => 'extension-attribute-message-consumer',
					'device' => [
						'identifier' => $entity->getDevice(),
					],
				],
			);

			return true;
		}

		$attributeIdentifier = null;

		// HARDWARE INFO
		if (
			$entity->getExtension()->equalsValue(Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_HARDWARE)
			&& $entity->getParameter() === Entities\Messages\ExtensionAttribute::MANUFACTURER
		) {
			$attributeIdentifier = Types\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MANUFACTURER;

		} elseif (
			$entity->getExtension()->equalsValue(Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_HARDWARE)
			&& $entity->getParameter() === Entities\Messages\ExtensionAttribute::MODEL
		) {
			$attributeIdentifier = Types\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MODEL;

		} elseif (
			$entity->getExtension()->equalsValue(Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_HARDWARE)
			&& $entity->getParameter() === Entities\Messages\ExtensionAttribute::VERSION
		) {
			$attributeIdentifier = Types\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_VERSION;

		} elseif (
			$entity->getExtension()->equalsValue(Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_HARDWARE)
			&& $entity->getParameter() === Entities\Messages\ExtensionAttribute::MAC_ADDRESS
		) {
			$attributeIdentifier = Types\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS;

			// FIRMWARE INFO
		} elseif (
			$entity->getExtension()->equalsValue(Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_FIRMWARE)
			&& $entity->getParameter() === Entities\Messages\ExtensionAttribute::MANUFACTURER
		) {
			$attributeIdentifier = Types\DeviceAttributeIdentifier::IDENTIFIER_FIRMWARE_MANUFACTURER;

		} elseif (
			$entity->getExtension()->equalsValue(Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_FIRMWARE)
			&& $entity->getParameter() === Entities\Messages\ExtensionAttribute::NAME
		) {
			$attributeIdentifier = Types\DeviceAttributeIdentifier::IDENTIFIER_FIRMWARE_NAME;

		} elseif (
			$entity->getExtension()->equalsValue(Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_FIRMWARE)
			&& $entity->getParameter() === Entities\Messages\ExtensionAttribute::VERSION
		) {
			$attributeIdentifier = Types\DeviceAttributeIdentifier::IDENTIFIER_FIRMWARE_VERSION;
		}

		if ($attributeIdentifier === null) {
			return true;
		}

		$attribute = $device->findAttribute($attributeIdentifier);

		if ($attribute === null) {
			$this->logger->error(
				sprintf('Device attribute "%s" is not registered', $entity->getParameter()),
				[
					'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
					'type' => 'extension-attribute-message-consumer',
					'device' => [
						'identifier' => $entity->getDevice(),
					],
					'attribute' => [
						'identifier' => $entity->getParameter(),
					],
				],
			);

			return true;
		}

		$this->databaseHelper->transaction(function () use ($entity, $attribute): void {
			$toUpdate = [
				'content' => $entity->getValue(),
			];

			$this->attributesManager->update($attribute, Utils\ArrayHash::from($toUpdate));
		});

		$this->logger->debug(
			'Consumed extension attribute message',
			[
				'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
				'type' => 'extension-attribute-message-consumer',
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
