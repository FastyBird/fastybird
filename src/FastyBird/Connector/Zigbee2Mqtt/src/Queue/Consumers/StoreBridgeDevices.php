<?php declare(strict_types = 1);

/**
 * StoreBridgeDevices.php
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
use Exception;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use TypeError;
use ValueError;
use function array_merge;
use function assert;
use function implode;
use function preg_match;

/**
 * Store bridge devices message consumer
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreBridgeDevices implements Queue\Consumer
{

	use Nette\SmartObject;
	use DeviceProperty;
	use ChannelProperty;

	public function __construct(
		protected readonly Zigbee2Mqtt\Logger $logger,
		protected readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		protected readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		protected readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		protected readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		protected readonly ApplicationHelpers\Database $databaseHelper,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\StoreBridgeDevices) {
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

		foreach ($entity->getDevices() as $deviceDescription) {
			if ($bridge->getIdentifier() === $deviceDescription->getIeeeAddress()) {
				$device = $bridge;

			} else {
				$findDeviceQuery = new Queries\Entities\FindSubDevices();
				$findDeviceQuery->byConnectorId($entity->getConnector());
				$findDeviceQuery->forParent($bridge);
				$findDeviceQuery->byIdentifier($deviceDescription->getIeeeAddress());

				$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\SubDevice::class);
			}

			if ($device === null) {
				$findDeviceQuery = new Queries\Entities\FindDevices();
				$findDeviceQuery->byConnectorId($entity->getConnector());
				$findDeviceQuery->byIdentifier($deviceDescription->getIeeeAddress());

				if (
					$this->devicesRepository->getResultSet(
						$findDeviceQuery,
						Entities\Zigbee2MqttDevice::class,
					)->count() !== 0
				) {
					$this->logger->error(
						'There is already registered device with same ieee address',
						[
							'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
							'type' => 'store-bridge-devices-message-consumer',
							'connector' => [
								'id' => $entity->getConnector()->toString(),
							],
							'bridge' => [
								'id' => $bridge->getId()->toString(),
							],
							'device' => [
								'identifier' => $deviceDescription->getIeeeAddress(),
							],
							'data' => $deviceDescription->toArray(),
						],
					);

					continue;
				}

				$connector = $this->connectorsRepository->find(
					$entity->getConnector(),
					Entities\Zigbee2MqttConnector::class,
				);

				if ($connector === null) {
					$this->logger->error(
						'Connector could not be loaded',
						[
							'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
							'type' => 'store-bridge-devices-message-consumer',
							'connector' => [
								'id' => $entity->getConnector()->toString(),
							],
							'bridge' => [
								'id' => $bridge->getId()->toString(),
							],
							'device' => [
								'identifier' => $deviceDescription->getIeeeAddress(),
							],
							'data' => $deviceDescription->toArray(),
						],
					);

					return true;
				}

				$device = $this->databaseHelper->transaction(
					function () use ($connector, $bridge, $deviceDescription): Entities\Devices\SubDevice {
						$device = $this->devicesManager->create(Utils\ArrayHash::from([
							'entity' => Entities\Devices\SubDevice::class,
							'connector' => $connector,
							'parent' => $bridge,
							'identifier' => $deviceDescription->getIeeeAddress(),
							'name' => $deviceDescription->getDefinition()?->getDescription(),
							'comment' => $deviceDescription->getDescription(),
						]));
						assert($device instanceof Entities\Devices\SubDevice);

						return $device;
					},
				);

				$this->logger->info(
					'Sub-device was created',
					[
						'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
						'type' => 'store-bridge-devices-message-consumer',
						'connector' => [
							'id' => $device->getConnector()->getId()->toString(),
						],
						'bridge' => [
							'id' => $device->getBridge()->getId()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
							'identifier' => $deviceDescription->getIeeeAddress(),
						],
					],
				);
			}

			$this->setDeviceProperty(
				$device->getId(),
				$deviceDescription->getFriendlyName(),
				MetadataTypes\DataType::STRING,
				Types\DevicePropertyIdentifier::FRIENDLY_NAME,
				DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::FRIENDLY_NAME),
			);
			$this->setDeviceProperty(
				$device->getId(),
				$deviceDescription->getIeeeAddress(),
				MetadataTypes\DataType::STRING,
				Types\DevicePropertyIdentifier::IEEE_ADDRESS,
				DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::IEEE_ADDRESS),
			);
			$this->setDeviceProperty(
				$device->getId(),
				$deviceDescription->isDisabled(),
				MetadataTypes\DataType::BOOLEAN,
				Types\DevicePropertyIdentifier::DISABLED,
				DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::DISABLED),
			);
			$this->setDeviceProperty(
				$device->getId(),
				$deviceDescription->isSupported(),
				MetadataTypes\DataType::BOOLEAN,
				Types\DevicePropertyIdentifier::SUPPORTED,
				DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::SUPPORTED),
			);
			$this->setDeviceProperty(
				$device->getId(),
				$deviceDescription->getType()->getValue(),
				MetadataTypes\DataType::STRING,
				Types\DevicePropertyIdentifier::TYPE,
				DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::TYPE),
			);
			$this->setDeviceProperty(
				$device->getId(),
				$deviceDescription->getDefinition()?->getModel(),
				MetadataTypes\DataType::STRING,
				Types\DevicePropertyIdentifier::MODEL,
				DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MODEL),
			);
			$this->setDeviceProperty(
				$device->getId(),
				$deviceDescription->getDefinition()?->getVendor(),
				MetadataTypes\DataType::STRING,
				Types\DevicePropertyIdentifier::MANUFACTURER,
				DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MANUFACTURER),
			);

			if ($device instanceof Entities\Devices\SubDevice) {
				$this->processExposes($device, $deviceDescription->getDefinition()?->getExposes() ?? []);
			}
		}

		$this->logger->debug(
			'Consumed bridge devices list message',
			[
				'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
				'type' => 'store-bridge-devices-message-consumer',
				'connector' => [
					'id' => $entity->getConnector()->toString(),
				],
				'bridge' => [
					'id' => $bridge->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

	/**
	 * @param array<Entities\Messages\Exposes\Type> $exposes
	 * @param array<string> $identifiers
	 *
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function processExposes(
		Entities\Devices\SubDevice $device,
		array $exposes,
		array $identifiers = [],
	): void
	{
		foreach ($exposes as $expose) {
			if ($expose instanceof Entities\Messages\Exposes\ListType) {
				$this->logger->warning(
					'List type expose is not supported',
					[
						'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
						'type' => 'store-bridge-devices-message-consumer',
						'connector' => [
							'id' => $device->getConnector()->getId()->toString(),
						],
						'bridge' => [
							'id' => $device->getBridge()->getId()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
							'identifier' => $device->getIdentifier(),
						],
						'data' => $expose->toArray(),
					],
				);

				continue;
			}

			if (
				$expose instanceof Entities\Messages\Exposes\ClimateType
				|| $expose instanceof Entities\Messages\Exposes\CoverType
				|| $expose instanceof Entities\Messages\Exposes\FanType
				|| $expose instanceof Entities\Messages\Exposes\LightType
				|| $expose instanceof Entities\Messages\Exposes\LockType
				|| $expose instanceof Entities\Messages\Exposes\SwitchType
			) {
				$this->processExposes(
					$device,
					$expose->getFeatures(),
					array_merge($identifiers, [$expose->getType()->getValue()]),
				);

			} elseif (
				$expose instanceof Entities\Messages\Exposes\BinaryType
				|| $expose instanceof Entities\Messages\Exposes\EnumType
				|| $expose instanceof Entities\Messages\Exposes\NumericType
				|| $expose instanceof Entities\Messages\Exposes\TextType
				|| $expose instanceof Entities\Messages\Exposes\CompositeType
			) {
				$channelIdentifier = implode(
					'_',
					array_merge($identifiers, [$expose->getType()->getValue(), $expose->getProperty()]),
				);

				if (
					preg_match(Zigbee2Mqtt\Constants::CHANNEL_IDENTIFIER_REGEX, $channelIdentifier) !== 1
					&& preg_match(Zigbee2Mqtt\Constants::CHANNEL_SPECIAL_IDENTIFIER_REGEX, $channelIdentifier) !== 1
				) {
					$this->logger->error(
						'Channel identifier could not be generated',
						[
							'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
							'type' => 'store-bridge-devices-message-consumer',
							'connector' => [
								'id' => $device->getConnector()->getId()->toString(),
							],
							'bridge' => [
								'id' => $device->getBridge()->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
								'identifier' => $device->getIdentifier(),
							],
							'data' => $expose->toArray(),
						],
					);

					continue;
				}

				$channel = $this->createChannel(
					$channelIdentifier,
					$expose->getLabel() ?? $expose->getName(),
					$device,
				);

				if ($expose instanceof Entities\Messages\Exposes\CompositeType) {
					foreach ($expose->getFeatures() as $feature) {
						$this->setChannelProperty(
							DevicesEntities\Channels\Properties\Dynamic::class,
							$channel->getId(),
							null,
							$feature->getDataType(),
							$feature->getProperty(),
							$feature->getLabel() ?? $feature->getName(),
							$feature->getFormat(),
							$feature->getUnit(),
							null,
							$feature instanceof Entities\Messages\Exposes\NumericType ? $feature->getValueStep() : null,
							$feature->isSettable(),
							$feature->isQueryable(),
						);
					}
				} else {
					$this->setChannelProperty(
						DevicesEntities\Channels\Properties\Dynamic::class,
						$channel->getId(),
						null,
						$expose->getDataType(),
						$expose->getProperty(),
						$expose->getLabel() ?? $expose->getName(),
						$expose->getFormat(),
						$expose->getUnit(),
						null,
						$expose instanceof Entities\Messages\Exposes\NumericType ? $expose->getValueStep() : null,
						$expose->isSettable(),
						$expose->isQueryable(),
					);
				}
			}
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 */
	private function createChannel(
		string $identifier,
		string|null $name,
		Entities\Devices\SubDevice $device,
	): DevicesEntities\Channels\Channel
	{
		return $this->databaseHelper->transaction(
			function () use ($identifier, $name, $device): DevicesEntities\Channels\Channel {
				$findChannelQuery = new Queries\Entities\FindChannels();
				$findChannelQuery->byIdentifier($identifier);
				$findChannelQuery->forDevice($device);

				$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Zigbee2MqttChannel::class);

				if ($channel === null) {
					$channel = $this->channelsManager->create(Utils\ArrayHash::from([
						'entity' => Entities\Zigbee2MqttChannel::class,
						'device' => $device,
						'identifier' => $identifier,
						'name' => $name,
					]));

					$this->logger->debug(
						'Device channel was created',
						[
							'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
							'type' => 'store-bridge-devices-message-consumer',
							'connector' => [
								'id' => $device->getConnector()->getId()->toString(),
							],
							'bridge' => [
								'id' => $device->getBridge()->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
								'identifier' => $device->getIdentifier(),
							],
							'channel' => [
								'id' => $channel->getId()->toString(),
							],
						],
					);
				}

				return $channel;
			},
		);
	}

}
