<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 * @since          0.4.0
 *
 * @date           05.02.22
 */

namespace FastyBird\Connector\FbMqtt\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\Connector\FbMqtt\Consumers;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette;
use Nette\Utils;
use Psr\Log;
use function in_array;
use function is_array;
use function sprintf;

/**
 * Device attributes MQTT message consumer
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device implements Consumers\Consumer
{

	use Nette\SmartObject;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $devicePropertiesManager,
		private readonly DevicesModels\Devices\Controls\ControlsManager $deviceControlManager,
		private readonly DevicesModels\Devices\Attributes\AttributesManager $deviceAttributesManager,
		private readonly DevicesModels\Channels\ChannelsManager $channelsManager,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
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
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\DeviceAttribute) {
			return false;
		}

		if ($entity->getAttribute() === Entities\Messages\Attribute::STATE) {
			$findDeviceQuery = new DevicesQueries\FindDevices();
			$findDeviceQuery->byConnectorId($entity->getConnector());
			$findDeviceQuery->byIdentifier($entity->getDevice());

			$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\FbMqttDevice::class);

			if ($device === null) {
				$this->logger->error(
					sprintf('Device "%s" is not registered', $entity->getDevice()),
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
						'type' => 'device-message-consumer',
						'device' => [
							'identifier' => $entity->getDevice(),
						],
					],
				);

				return true;
			}

			if (MetadataTypes\ConnectionState::isValidValue($entity->getValue())) {
				$this->deviceConnectionManager->setState(
					$device,
					MetadataTypes\ConnectionState::get($entity->getValue()),
				);
			}
		} else {
			$findDeviceQuery = new DevicesQueries\FindDevices();
			$findDeviceQuery->byIdentifier($entity->getDevice());

			$device = $this->devicesRepository->findOneBy($findDeviceQuery);

			if ($device === null) {
				$this->logger->error(
					sprintf('Device "%s" is not registered', $entity->getDevice()),
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
						'type' => 'device-message-consumer',
						'device' => [
							'identifier' => $entity->getDevice(),
						],
					],
				);

				return true;
			}

			$this->databaseHelper->transaction(function () use ($entity, $device): void {
				$toUpdate = [];

				if ($entity->getAttribute() === Entities\Messages\Attribute::NAME) {
					$toUpdate['name'] = $entity->getValue();
				}

				if (
					$entity->getAttribute() === Entities\Messages\Attribute::PROPERTIES
					&& is_array($entity->getValue())
				) {
					$this->setDeviceProperties($device, Utils\ArrayHash::from($entity->getValue()));
				}

				if (
					$entity->getAttribute() === Entities\Messages\Attribute::EXTENSIONS
					&& is_array($entity->getValue())
				) {
					$this->setDeviceExtensions($device, Utils\ArrayHash::from($entity->getValue()));
				}

				if (
					$entity->getAttribute() === Entities\Messages\Attribute::CHANNELS
					&& is_array($entity->getValue())
				) {
					$this->setDeviceChannels($device, Utils\ArrayHash::from($entity->getValue()));
				}

				if (
					$entity->getAttribute() === Entities\Messages\Attribute::CONTROLS
					&& is_array($entity->getValue())
				) {
					$this->setDeviceControls($device, Utils\ArrayHash::from($entity->getValue()));
				}

				if ($toUpdate !== []) {
					$this->devicesManager->update($device, Utils\ArrayHash::from($toUpdate));
				}
			});
		}

		$this->logger->debug(
			'Consumed device message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
				'type' => 'device-message-consumer',
				'device' => [
					'identifier' => $entity->getDevice(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

	/**
	 * @phpstan-param Utils\ArrayHash<string> $properties
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function setDeviceProperties(
		DevicesEntities\Devices\Device $device,
		Utils\ArrayHash $properties,
	): void
	{
		foreach ($properties as $propertyName) {
			if ($propertyName === Types\DevicePropertyIdentifier::IDENTIFIER_STATE) {
				$this->deviceConnectionManager->setState(
					$device,
					MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_UNKNOWN),
				);
			} else {
				if ($device->findProperty($propertyName) === null) {
					if (in_array($propertyName, [
						Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
						Types\DevicePropertyIdentifier::IDENTIFIER_STATUS_LED,
					], true)) {
						$this->devicePropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
							'device' => $device,
							'identifier' => $propertyName,
							'name' => $propertyName,
							'settable' => false,
							'queryable' => false,
							'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
						]));

					} elseif (in_array($propertyName, [
						Types\DevicePropertyIdentifier::IDENTIFIER_UPTIME,
						Types\DevicePropertyIdentifier::IDENTIFIER_FREE_HEAP,
						Types\DevicePropertyIdentifier::IDENTIFIER_CPU_LOAD,
						Types\DevicePropertyIdentifier::IDENTIFIER_VCC,
						Types\DevicePropertyIdentifier::IDENTIFIER_RSSI,
					], true)) {
						$this->devicePropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
							'device' => $device,
							'identifier' => $propertyName,
							'name' => $propertyName,
							'settable' => false,
							'queryable' => false,
							'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
						]));

					} else {
						$this->devicePropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
							'device' => $device,
							'identifier' => $propertyName,
							'settable' => false,
							'queryable' => false,
							'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UNKNOWN),
						]));
					}
				}
			}
		}

		// Cleanup for unused properties
		foreach ($device->getProperties() as $property) {
			if (!in_array($property->getIdentifier(), (array) $properties, true)) {
				$this->devicePropertiesManager->delete($property);
			}
		}
	}

	/**
	 * @phpstan-param Utils\ArrayHash<string> $extensions
	 */
	private function setDeviceExtensions(
		DevicesEntities\Devices\Device $device,
		Utils\ArrayHash $extensions,
	): void
	{
		foreach ($extensions as $extensionName) {
			if ($extensionName === Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_HARDWARE) {
				foreach ([
					Types\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS,
					Types\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MANUFACTURER,
					Types\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MODEL,
					Types\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_VERSION,
				] as $attributeName) {
					if ($device->findAttribute($attributeName) === null) {
						$this->deviceAttributesManager->create(Utils\ArrayHash::from([
							'device' => $device,
							'identifier' => $attributeName,
						]));
					}
				}
			} elseif ($extensionName === Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_FIRMWARE) {
				foreach ([
					Types\DeviceAttributeIdentifier::IDENTIFIER_FIRMWARE_MANUFACTURER,
					Types\DeviceAttributeIdentifier::IDENTIFIER_FIRMWARE_NAME,
					Types\DeviceAttributeIdentifier::IDENTIFIER_FIRMWARE_VERSION,
				] as $attributeName) {
					if ($device->findAttribute($attributeName) === null) {
						$this->deviceAttributesManager->create(Utils\ArrayHash::from([
							'device' => $device,
							'identifier' => $attributeName,
						]));
					}
				}
			}
		}
	}

	/**
	 * @phpstan-param Utils\ArrayHash<string> $controls
	 *
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	private function setDeviceControls(
		DevicesEntities\Devices\Device $device,
		Utils\ArrayHash $controls,
	): void
	{
		foreach ($controls as $controlName) {
			if ($device->findControl($controlName) === null) {
				$this->deviceControlManager->create(Utils\ArrayHash::from([
					'device' => $device,
					'name' => $controlName,
				]));
			}
		}

		// Cleanup for unused control
		foreach ($device->getControls() as $control) {
			if (!in_array($control->getName(), (array) $controls, true)) {
				$this->deviceControlManager->delete($control);
			}
		}
	}

	/**
	 * @phpstan-param Utils\ArrayHash<string> $channels
	 *
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	private function setDeviceChannels(
		DevicesEntities\Devices\Device $device,
		Utils\ArrayHash $channels,
	): void
	{
		foreach ($channels as $channelName) {
			if ($device->findChannel($channelName) === null) {
				$this->channelsManager->create(Utils\ArrayHash::from([
					'device' => $device,
					'identifier' => $channelName,
				]));
			}
		}

		// Cleanup for unused channels
		foreach ($device->getChannels() as $channel) {
			if (!in_array($channel->getIdentifier(), (array) $channels, true)) {
				$this->channelsManager->delete($channel);
			}
		}
	}

}
