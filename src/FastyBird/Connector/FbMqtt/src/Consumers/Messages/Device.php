<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 * @since          1.0.0
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

	public function __construct(
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicePropertiesManager,
		private readonly DevicesModels\Entities\Devices\Controls\ControlsRepository $deviceControlsRepository,
		private readonly DevicesModels\Entities\Devices\Controls\ControlsManager $deviceControlsManager,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DevicesUtilities\Database $databaseHelper,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\DeviceAttribute) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\Entities\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getDevice());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\FbMqttDevice::class);

		if ($entity->getAttribute() === Entities\Messages\Attribute::STATE) {
			if (MetadataTypes\ConnectionState::isValidValue($entity->getValue())) {
				if ($device === null) {
					$device = $this->devicesManager->create(Utils\ArrayHash::from([
						'identifier' => $entity->getDevice(),
					]));
				}

				$this->deviceConnectionManager->setState(
					$device,
					MetadataTypes\ConnectionState::get($entity->getValue()),
				);
			}
		} else {
			$this->databaseHelper->transaction(function () use ($entity, $device): void {
				$toUpdate = [];

				if ($entity->getAttribute() === Entities\Messages\Attribute::NAME) {
					$toUpdate['name'] = $entity->getValue();
				}

				if ($device === null) {
					$toUpdate['identifier'] = $entity->getDevice();

					$device = $this->devicesManager->create(Utils\ArrayHash::from($toUpdate));

				} elseif ($toUpdate !== []) {
					$this->devicesManager->update($device, Utils\ArrayHash::from($toUpdate));
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
	 * @param Utils\ArrayHash<string> $properties
	 *
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
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
				$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($device);
				$findDevicePropertyQuery->byIdentifier($propertyName);

				if ($this->devicePropertiesRepository->findOneBy($findDevicePropertyQuery) === null) {
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

		$findDevicePropertiesQuery = new DevicesQueries\Entities\FindDeviceProperties();
		$findDevicePropertiesQuery->forDevice($device);

		// Cleanup for unused properties
		foreach ($this->devicePropertiesRepository->findAllBy($findDevicePropertiesQuery) as $property) {
			if (!in_array($property->getIdentifier(), (array) $properties, true)) {
				$this->devicePropertiesManager->delete($property);
			}
		}
	}

	/**
	 * @param Utils\ArrayHash<string> $extensions
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	private function setDeviceExtensions(
		DevicesEntities\Devices\Device $device,
		Utils\ArrayHash $extensions,
	): void
	{
		foreach ($extensions as $extensionName) {
			if ($extensionName === Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_HARDWARE) {
				foreach ([
					Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS,
					Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MANUFACTURER,
					Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MODEL,
					Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_VERSION,
				] as $propertyName) {
					$findPropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
					$findPropertyQuery->forDevice($device);
					$findPropertyQuery->byIdentifier($propertyName);

					if ($this->devicePropertiesRepository->findOneBy($findPropertyQuery) === null) {
						$this->devicePropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Devices\Properties\Variable::class,
							'device' => $device,
							'identifier' => $propertyName,
							'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
						]));
					}
				}
			} elseif ($extensionName === Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_FIRMWARE) {
				foreach ([
					Types\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_MANUFACTURER,
					Types\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_NAME,
					Types\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_VERSION,
				] as $propertyName) {
					$findPropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
					$findPropertyQuery->forDevice($device);
					$findPropertyQuery->byIdentifier($propertyName);

					if ($this->devicePropertiesRepository->findOneBy($findPropertyQuery) === null) {
						$this->devicePropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Devices\Properties\Variable::class,
							'device' => $device,
							'identifier' => $propertyName,
							'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
						]));
					}
				}
			}
		}
	}

	/**
	 * @param Utils\ArrayHash<string> $controls
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	private function setDeviceControls(
		DevicesEntities\Devices\Device $device,
		Utils\ArrayHash $controls,
	): void
	{
		foreach ($controls as $controlName) {
			$findDeviceControlQuery = new DevicesQueries\Entities\FindDeviceControls();
			$findDeviceControlQuery->forDevice($device);
			$findDeviceControlQuery->byName($controlName);

			$control = $this->deviceControlsRepository->findOneBy($findDeviceControlQuery);

			if ($control === null) {
				$this->deviceControlsManager->create(Utils\ArrayHash::from([
					'device' => $device,
					'name' => $controlName,
				]));
			}
		}

		$findDeviceControlsQuery = new DevicesQueries\Entities\FindDeviceControls();
		$findDeviceControlsQuery->forDevice($device);

		// Cleanup for unused control
		foreach ($this->deviceControlsRepository->findAllBy($findDeviceControlsQuery) as $control) {
			if (!in_array($control->getName(), (array) $controls, true)) {
				$this->deviceControlsManager->delete($control);
			}
		}
	}

	/**
	 * @param Utils\ArrayHash<string> $channels
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	private function setDeviceChannels(
		DevicesEntities\Devices\Device $device,
		Utils\ArrayHash $channels,
	): void
	{
		foreach ($channels as $channelName) {
			$findChannelQuery = new DevicesQueries\Entities\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier($channelName);

			$channel = $this->channelsRepository->findOneBy($findChannelQuery);

			if ($channel === null) {
				$this->channelsManager->create(Utils\ArrayHash::from([
					'device' => $device,
					'identifier' => $channelName,
				]));
			}
		}

		$findChannelsQuery = new DevicesQueries\Entities\FindChannels();
		$findChannelsQuery->forDevice($device);

		// Cleanup for unused channels
		foreach ($this->channelsRepository->findAllBy($findChannelsQuery) as $channel) {
			if (!in_array($channel->getIdentifier(), (array) $channels, true)) {
				$this->channelsManager->delete($channel);
			}
		}
	}

}
