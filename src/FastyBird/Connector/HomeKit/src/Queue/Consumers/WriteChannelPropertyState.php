<?php declare(strict_types = 1);

/**
 * WriteChannelPropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           30.11.23
 */

namespace FastyBird\Connector\HomeKit\Queue\Consumers;

use FastyBird\Connector\HomeKit;
use FastyBird\Connector\HomeKit\Clients;
use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Protocol;
use FastyBird\Connector\HomeKit\Queue;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use RuntimeException;
use function intval;

/**
 * Write state to device message consumer
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WriteChannelPropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Protocol\Driver $accessoryDriver,
		private readonly Clients\Subscriber $subscriber,
		private readonly HomeKit\Logger $logger,
		private readonly DevicesModels\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\WriteChannelPropertyState) {
			return false;
		}

		$findConnectorQuery = new DevicesQueries\Configuration\FindConnectors();
		$findConnectorQuery->byId($entity->getConnector());
		$findConnectorQuery->byType(Entities\HomeKitConnector::TYPE);

		$connector = $this->connectorsConfigurationRepository->findOneBy($findConnectorQuery);

		if ($connector === null) {
			$this->logger->error(
				'Connector could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_HOMEKIT,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'id' => $entity->getDevice()->toString(),
					],
					'channel' => [
						'id' => $entity->getChannel()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byId($entity->getDevice());
		$findDeviceQuery->byType(Entities\HomeKitDevice::TYPE);

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_HOMEKIT,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $entity->getDevice()->toString(),
					],
					'channel' => [
						'id' => $entity->getChannel()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$accessory = $this->accessoryDriver->findAccessory($device->getId());

		if (!$accessory instanceof Entities\Protocol\Accessories\Generic) {
			$this->logger->warning(
				'Accessory for received channel property message was not found in accessory driver',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_HOMEKIT,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $entity->getChannel()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byId($entity->getChannel());
		$findChannelQuery->byType(Entities\HomeKitChannel::TYPE);

		$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

		if ($channel === null) {
			$this->logger->error(
				'Channel could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_HOMEKIT,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $entity->getChannel()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$property = $this->channelsPropertiesConfigurationRepository->find($entity->getProperty());

		if ($property === null) {
			$this->logger->error(
				'Channel property could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_HOMEKIT,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $channel->getId()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		foreach ($accessory->getServices() as $service) {
			foreach ($service->getCharacteristics() as $characteristic) {
				if (
					$characteristic->getProperty() !== null
					&& $characteristic->getProperty()->getId()->equals($property->getId())
				) {
					if ($property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty) {
						$parent = $this->channelsPropertiesConfigurationRepository->find($property->getParent());

						if ($parent instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
							if ($entity->getState() !== null) {
								if ($entity->getState()->getExpectedValue() !== null) {
									$characteristic->setActualValue($entity->getState()->getExpectedValue());
									$characteristic->setValid($entity->getState()->isValid());
								} elseif (
									$entity->getState()->getActualValue() !== null
									&& $entity->getState()->isValid()
								) {
									$characteristic->setActualValue($entity->getState()->getActualValue());
									$characteristic->setValid($entity->getState()->isValid());
								}
							} else {
								$this->logger->warning(
									'State entity is missing in event entity',
									[
										'source' => MetadataTypes\ConnectorSource::CONNECTOR_HOMEKIT,
										'type' => 'write-channel-property-state-message-consumer',
										'connector' => [
											'id' => $connector->getId()->toString(),
										],
										'device' => [
											'id' => $device->getId()->toString(),
										],
										'channel' => [
											'id' => $channel->getId()->toString(),
										],
										'property' => [
											'id' => $property->getId()->toString(),
										],
										'hap' => $accessory->toHap(),
									],
								);

								continue;
							}
						} elseif ($parent instanceof MetadataDocuments\DevicesModule\ChannelVariableProperty) {
							$characteristic->setActualValue($parent->getValue());
							$characteristic->setValid(true);
						}
					} elseif ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
						if ($entity->getState() !== null) {
							if ($entity->getState()->getExpectedValue() !== null) {
								$characteristic->setActualValue($entity->getState()->getExpectedValue());
								$characteristic->setValid(true);
							}
						} else {
							$this->logger->warning(
								'State entity is missing in event entity',
								[
									'source' => MetadataTypes\ConnectorSource::CONNECTOR_HOMEKIT,
									'type' => 'write-channel-property-state-message-consumer',
									'connector' => [
										'id' => $connector->getId()->toString(),
									],
									'device' => [
										'id' => $device->getId()->toString(),
									],
									'channel' => [
										'id' => $channel->getId()->toString(),
									],
									'property' => [
										'id' => $property->getId()->toString(),
									],
									'hap' => $accessory->toHap(),
								],
							);

							continue;
						}
					} elseif ($property instanceof MetadataDocuments\DevicesModule\ChannelVariableProperty) {
						$characteristic->setActualValue($property->getValue());
						$characteristic->setValid(true);
					}

					if (!$characteristic->isVirtual()) {
						$this->subscriber->publish(
							intval($accessory->getAid()),
							intval($accessory->getIidManager()->getIid($characteristic)),
							Protocol\Transformer::toClient(
								$characteristic->getProperty(),
								$characteristic->getDataType(),
								$characteristic->getValidValues(),
								$characteristic->getMaxLength(),
								$characteristic->getMinValue(),
								$characteristic->getMaxValue(),
								$characteristic->getMinStep(),
								$characteristic->getValue(),
							),
							$characteristic->immediateNotify(),
						);
					} else {
						foreach ($service->getCharacteristics() as $serviceCharacteristic) {
							$this->subscriber->publish(
								intval($accessory->getAid()),
								intval($accessory->getIidManager()->getIid($serviceCharacteristic)),
								Protocol\Transformer::toClient(
									$serviceCharacteristic->getProperty(),
									$serviceCharacteristic->getDataType(),
									$serviceCharacteristic->getValidValues(),
									$serviceCharacteristic->getMaxLength(),
									$serviceCharacteristic->getMinValue(),
									$serviceCharacteristic->getMaxValue(),
									$serviceCharacteristic->getMinStep(),
									$serviceCharacteristic->getValue(),
								),
								$serviceCharacteristic->immediateNotify(),
							);
						}
					}
				}
			}
		}

		$this->logger->debug(
			'Consumed write device state message',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_HOMEKIT,
				'type' => 'write-channel-property-state-message-consumer',
				'connector' => [
					'id' => $connector->getId()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'channel' => [
					'id' => $channel->getId()->toString(),
				],
				'property' => [
					'id' => $property->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
