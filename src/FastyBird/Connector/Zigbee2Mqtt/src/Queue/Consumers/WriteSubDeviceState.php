<?php declare(strict_types = 1);

/**
 * WriteSubDeviceState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           32.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Consumers;

use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\API;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use Nette\Utils;
use stdClass;
use Throwable;
use function array_key_exists;
use function preg_match;
use function sprintf;

/**
 * Write state to sub-device message consumer
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WriteSubDeviceState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		protected readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		protected readonly DevicesModels\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Queue\Queue $queue,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Helpers\Entity $entityHelper,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Helpers\Devices\Bridge $bridgeHelper,
		private readonly Helpers\Devices\SubDevice $subDeviceHelper,
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly DevicesModels\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\WriteSubDeviceState) {
			return false;
		}

		$findConnectorQuery = new DevicesQueries\Configuration\FindConnectors();
		$findConnectorQuery->byId($entity->getConnector());
		$findConnectorQuery->byType(Entities\Zigbee2MqttConnector::TYPE);

		$connector = $this->connectorsConfigurationRepository->findOneBy($findConnectorQuery);

		if ($connector === null) {
			$this->logger->error(
				'Connector could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_ZIGBEE2MQTT,
					'type' => 'write-sub-device-state-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'id' => $entity->getDevice()->toString(),
					],
					'channel' => [
						'id' => $entity->getChannel()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byId($entity->getDevice());
		$findDeviceQuery->byType(Entities\Devices\SubDevice::TYPE);

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_ZIGBEE2MQTT,
					'type' => 'write-sub-device-state-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'id' => $entity->getDevice()->toString(),
					],
					'channel' => [
						'id' => $entity->getChannel()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$bridge = $this->subDeviceHelper->getBridge($device);

		$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byId($entity->getChannel());
		$findChannelQuery->byType(Entities\Zigbee2MqttChannel::TYPE);

		$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

		if ($channel === null) {
			$this->logger->error(
				'Channel could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_ZIGBEE2MQTT,
					'type' => 'write-sub-device-state-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'id' => $entity->getDevice()->toString(),
					],
					'channel' => [
						'id' => $entity->getChannel()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$findPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
		$findPropertiesQuery->forChannel($channel);
		$findPropertiesQuery->settable(true);

		$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
			$findPropertiesQuery,
			MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
		);

		if (
			preg_match(Zigbee2Mqtt\Constants::CHANNEL_IDENTIFIER_REGEX, $channel->getIdentifier(), $matches) === 1
			&& array_key_exists('type', $matches)
			&& Types\ExposeType::isValidValue($matches['type'])
			&& array_key_exists('identifier', $matches)
		) {
			$writeData = new stdClass();

			foreach ($properties as $property) {
				$state = $this->channelPropertiesStatesManager->get($property);

				if ($state?->getExpectedValue() !== null) {
					$writeData->{$property->getIdentifier()} = MetadataUtilities\Value::flattenValue(
						$state->getExpectedValue(),
					);
				}
			}

			if ($matches['type'] === Types\ExposeType::COMPOSITE) {
				$payload = new stdClass();
				$payload->{$matches['identifier']} = $writeData;
			} else {
				$payload = $writeData;
			}
		} elseif (
			preg_match(
				Zigbee2Mqtt\Constants::CHANNEL_SPECIAL_IDENTIFIER_REGEX,
				$channel->getIdentifier(),
				$matches,
			) === 1
			&& array_key_exists('type', $matches)
			&& Types\ExposeType::isValidValue($matches['type'])
			&& array_key_exists('subtype', $matches)
			&& Types\ExposeType::isValidValue($matches['subtype'])
			&& array_key_exists('identifier', $matches)
		) {
			$writeData = new stdClass();

			foreach ($properties as $property) {
				$state = $this->channelPropertiesStatesManager->get($property);

				if ($state?->getExpectedValue() !== null) {
					$writeData->{$property->getIdentifier()} = MetadataUtilities\Value::flattenValue(
						$state->getExpectedValue(),
					);
				}
			}

			if ($matches['subtype'] === Types\ExposeType::COMPOSITE) {
				$payload = new stdClass();
				$payload->{$matches['identifier']} = $writeData;
			} else {
				$payload = $writeData;
			}
		} else {
			$this->logger->error(
				'Channel identifier has invalid value',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_ZIGBEE2MQTT,
					'type' => 'write-sub-device-state-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'id' => $entity->getDevice()->toString(),
					],
					'channel' => [
						'id' => $entity->getChannel()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		try {
			$this->getClient($connector)
				->publish(
					sprintf(
						'%s/%s/set',
						$this->bridgeHelper->getBaseTopic($bridge),
						$this->subDeviceHelper->getFriendlyName($device) ?? $this->subDeviceHelper->getIeeeAddress(
							$device,
						),
					),
					Utils\Json::encode($payload),
				)
				->then(function () use ($channel): void {
					$findPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
					$findPropertiesQuery->forChannel($channel);
					$findPropertiesQuery->settable(true);

					$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
						$findPropertiesQuery,
						MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
					);

					foreach ($properties as $property) {
						$state = $this->channelPropertiesStatesManager->get($property);

						if ($state?->getExpectedValue() !== null) {
							$this->channelPropertiesStatesManager->setPendingState($property, true);
						}
					}
				})
				->catch(function (Throwable $ex) use ($entity, $connector, $bridge, $channel): void {
					$findPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
					$findPropertiesQuery->forChannel($channel);
					$findPropertiesQuery->settable(true);

					$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
						$findPropertiesQuery,
						MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
					);

					foreach ($properties as $property) {
						$this->channelPropertiesStatesManager->setPendingState($property, false);
					}

					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $connector->getId(),
								'base_topic' => $this->bridgeHelper->getBaseTopic($bridge),
								'identifier' => $bridge->getIdentifier(),
								'state' => Types\ConnectionState::UNKNOWN,
							],
						),
					);

					$this->logger->error(
						'Could write state to sub-device',
						[
							'source' => MetadataTypes\ConnectorSource::CONNECTOR_ZIGBEE2MQTT,
							'type' => 'write-sub-device-state-message-consumer',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $entity->getConnector()->toString(),
							],
							'device' => [
								'id' => $entity->getDevice()->toString(),
							],
							'channel' => [
								'id' => $entity->getChannel()->toString(),
							],
							'data' => $entity->toArray(),
						],
					);
				});
		} catch (Throwable $ex) {
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_ZIGBEE2MQTT,
					'type' => 'write-sub-device-state-message-consumer',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'id' => $entity->getDevice()->toString(),
					],
					'channel' => [
						'id' => $entity->getChannel()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);
		}

		$this->logger->debug(
			'Consumed write sub-device state message',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_ZIGBEE2MQTT,
				'type' => 'write-sub-device-state-message-consumer',
				'connector' => [
					'id' => $entity->getConnector()->toString(),
				],
				'device' => [
					'id' => $entity->getDevice()->toString(),
				],
				'channel' => [
					'id' => $entity->getChannel()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function getClient(MetadataDocuments\DevicesModule\Connector $connector): API\Client
	{
		return $this->connectionManager->getClient(
			$connector->getId()->toString(),
			$this->connectorHelper->getServerAddress($connector),
			$this->connectorHelper->getServerPort($connector),
			$this->connectorHelper->getUsername($connector),
			$this->connectorHelper->getPassword($connector),
		);
	}

}
