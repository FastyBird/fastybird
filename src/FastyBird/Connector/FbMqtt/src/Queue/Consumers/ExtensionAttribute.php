<?php declare(strict_types = 1);

/**
 * ExtensionAttribute.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           05.07.22
 */

namespace FastyBird\Connector\FbMqtt\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Queries;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\Connector\FbMqtt\Types;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use Nette\Utils;
use function sprintf;

/**
 * Device extension MQTT message consumer
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ExtensionAttribute implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly FbMqtt\Logger $logger,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $deviceRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $propertiesManager,
		private readonly ApplicationHelpers\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\ExtensionAttribute) {
			return false;
		}

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byIdentifier($message->getDevice());

		$device = $this->deviceRepository->findOneBy($findDeviceQuery, Entities\Devices\Device::class);

		if ($device === null) {
			$this->logger->warning(
				sprintf('Device "%s" is not registered', $message->getDevice()),
				[
					'source' => MetadataTypes\Sources\Connector::FB_MQTT,
					'type' => 'extension-attribute-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'identifier' => $message->getDevice(),
					],
				],
			);

			return true;
		}

		$propertyIdentifier = null;

		// HARDWARE INFO
		if (
			$message->getExtension()->equalsValue(Types\ExtensionType::FASTYBIRD_HARDWARE)
			&& $message->getParameter() === Queue\Messages\ExtensionAttribute::MANUFACTURER
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::HARDWARE_MANUFACTURER;

		} elseif (
			$message->getExtension()->equalsValue(Types\ExtensionType::FASTYBIRD_HARDWARE)
			&& $message->getParameter() === Queue\Messages\ExtensionAttribute::MODEL
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::HARDWARE_MODEL;

		} elseif (
			$message->getExtension()->equalsValue(Types\ExtensionType::FASTYBIRD_HARDWARE)
			&& $message->getParameter() === Queue\Messages\ExtensionAttribute::VERSION
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::HARDWARE_VERSION;

		} elseif (
			$message->getExtension()->equalsValue(Types\ExtensionType::FASTYBIRD_HARDWARE)
			&& $message->getParameter() === Queue\Messages\ExtensionAttribute::MAC_ADDRESS
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS;

			// FIRMWARE INFO
		} elseif (
			$message->getExtension()->equalsValue(Types\ExtensionType::FASTYBIRD_FIRMWARE)
			&& $message->getParameter() === Queue\Messages\ExtensionAttribute::MANUFACTURER
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::FIRMWARE_MANUFACTURER;

		} elseif (
			$message->getExtension()->equalsValue(Types\ExtensionType::FASTYBIRD_FIRMWARE)
			&& $message->getParameter() === Queue\Messages\ExtensionAttribute::NAME
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::FIRMWARE_NAME;

		} elseif (
			$message->getExtension()->equalsValue(Types\ExtensionType::FASTYBIRD_FIRMWARE)
			&& $message->getParameter() === Queue\Messages\ExtensionAttribute::VERSION
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::FIRMWARE_VERSION;
		}

		if ($propertyIdentifier === null) {
			return true;
		}

		$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier($propertyIdentifier);

		$property = $this->propertiesRepository->findOneBy($findDevicePropertyQuery);

		if ($property === null) {
			$this->logger->warning(
				sprintf('Device property "%s" is not registered', $message->getParameter()),
				[
					'source' => MetadataTypes\Sources\Connector::FB_MQTT,
					'type' => 'extension-attribute-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'property' => [
						'identifier' => $message->getParameter(),
					],
				],
			);

			return true;
		}

		$this->databaseHelper->transaction(function () use ($message, $property): void {
			$toUpdate = [
				'value' => $message->getValue(),
			];

			$this->propertiesManager->update($property, Utils\ArrayHash::from($toUpdate));
		});

		$this->logger->debug(
			'Consumed extension property message',
			[
				'source' => MetadataTypes\Sources\Connector::FB_MQTT,
				'type' => 'extension-attribute-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'property' => [
					'id' => $property->getId()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

}
