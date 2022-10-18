<?php declare(strict_types = 1);

/**
 * ChannelProperty.php
 *
 * @license        More in license.md
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
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Consumers;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use Psr\Log;
use Throwable;
use function array_merge;
use function assert;
use function count;
use function sprintf;

/**
 * Device channel property MQTT message consumer
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelProperty implements Consumers\Consumer
{

	use Nette\SmartObject;
	use TProperty;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DevicesModels\Devices\DevicesRepository $deviceRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesModels\DataStorage\DevicesRepository $devicesDataStorageRepository,
		private readonly DevicesModels\DataStorage\ChannelsRepository $channelsDataStorageRepository,
		private readonly DevicesModels\DataStorage\ChannelPropertiesRepository $propertiesDataStorageRepository,
		private readonly DevicesModels\States\ChannelPropertiesManager $propertiesStatesManager,
		private readonly DevicesModels\States\ChannelPropertiesRepository $propertyStateRepository,
		private readonly Helpers\Database $databaseHelper,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DBAL\Exception
	 * @throws MetadataExceptions\FileNotFound
	 * @throws Throwable
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\ChannelProperty) {
			return false;
		}

		if ($entity->getValue() !== FbMqtt\Constants::VALUE_NOT_SET) {
			$deviceItem = $this->devicesDataStorageRepository->findByIdentifier(
				$entity->getConnector(),
				$entity->getDevice(),
			);

			if ($deviceItem === null) {
				$this->logger->error(
					sprintf('Device "%s" is not registered', $entity->getDevice()),
					[
						'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
						'type' => 'channel-property-message-consumer',
						'device' => [
							'identifier' => $entity->getDevice(),
						],
					],
				);

				return true;
			}

			$channelItem = $this->channelsDataStorageRepository->findByIdentifier(
				$deviceItem->getId(),
				$entity->getChannel(),
			);

			if ($channelItem === null) {
				$this->logger->error(
					sprintf('Devices channel "%s" is not registered', $entity->getChannel()),
					[
						'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
						'type' => 'channel-property-message-consumer',
						'device' => [
							'identifier' => $entity->getDevice(),
						],
						'channel' => [
							'identifier' => $entity->getChannel(),
						],
					],
				);

				return true;
			}

			$propertyItem = $this->propertiesDataStorageRepository->findByIdentifier(
				$channelItem->getId(),
				$entity->getProperty(),
			);

			if ($propertyItem instanceof MetadataEntities\DevicesModule\ChannelVariableProperty) {
				$property = $this->databaseHelper->query(
					function () use ($propertyItem): DevicesEntities\Channels\Properties\Property|null {
						$findPropertyQuery = new DevicesQueries\FindChannelProperties();
						$findPropertyQuery->byId($propertyItem->getId());

						return $this->propertiesRepository->findOneBy($findPropertyQuery);
					},
				);
				assert($property instanceof DevicesEntities\Channels\Properties\Property);

				$this->databaseHelper->transaction(
					fn (): DevicesEntities\Channels\Properties\Property => $this->propertiesManager->update(
						$property,
						Utils\ArrayHash::from([
							'value' => $entity->getValue(),
						]),
					),
				);
			} elseif ($propertyItem instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty) {
				try {
					$propertyState = $this->propertyStateRepository->findOne($propertyItem);

				} catch (DevicesExceptions\NotImplemented) {
					$this->logger->warning(
						'States repository is not configured. State could not be fetched',
						[
							'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
							'type' => 'channel-property-message-consumer',
							'device' => [
								'id' => $deviceItem->getId()->toString(),
							],
							'channel' => [
								'id' => $channelItem->getId()->toString(),
							],
							'property' => [
								'id' => $propertyItem->getId()->toString(),
							],
						],
					);

					return true;
				}

				$actualValue = DevicesUtilities\ValueHelper::flattenValue(
					DevicesUtilities\ValueHelper::normalizeValue(
						$propertyItem->getDataType(),
						$entity->getValue(),
						$propertyItem->getFormat(),
						$propertyItem->getInvalid(),
					),
				);

				try {
					// In case synchronization failed...
					if ($propertyState === null) {
						// ...create state in storage
						$this->propertiesStatesManager->create(
							$propertyItem,
							Utils\ArrayHash::from(array_merge(
								$propertyItem->toArray(),
								[
									'actualValue' => $actualValue,
									'expectedValue' => null,
									'pending' => false,
									'valid' => true,
								],
							)),
						);

					} else {
						$this->propertiesStatesManager->update(
							$propertyItem,
							$propertyState,
							Utils\ArrayHash::from([
								'actualValue' => $actualValue,
								'valid' => true,
							]),
						);
					}
				} catch (DevicesExceptions\NotImplemented) {
					$this->logger->warning(
						'States manager is not configured. State could not be saved',
						[
							'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
							'type' => 'channel-property-message-consumer',
							'device' => [
								'id' => $deviceItem->getId()->toString(),
							],
							'channel' => [
								'id' => $channelItem->getId()->toString(),
							],
							'property' => [
								'id' => $propertyItem->getId()->toString(),
							],
						],
					);
				}
			}
		} else {
			$device = $this->databaseHelper->query(
				function () use ($entity): DevicesEntities\Devices\Device|null {
					$findDeviceQuery = new DevicesQueries\FindDevices();
					$findDeviceQuery->byIdentifier($entity->getDevice());

					return $this->deviceRepository->findOneBy($findDeviceQuery);
				},
			);

			if ($device === null) {
				$this->logger->error(
					sprintf('Device "%s" is not registered', $entity->getDevice()),
					[
						'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
						'type' => 'channel-property-message-consumer',
						'device' => [
							'identifier' => $entity->getDevice(),
						],
					],
				);

				return true;
			}

			$channel = $device->findChannel($entity->getChannel());

			if ($channel === null) {
				$this->logger->error(
					sprintf('Device channel "%s" is not registered', $entity->getChannel()),
					[
						'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
						'type' => 'channel-property-message-consumer',
						'device' => [
							'identifier' => $entity->getDevice(),
						],
						'channel' => [
							'identifier' => $entity->getChannel(),
						],
					],
				);

				return true;
			}

			$property = $channel->findProperty($entity->getProperty());

			if ($property === null) {
				$this->logger->error(
					sprintf('Property "%s" is not registered', $entity->getProperty()),
					[
						'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
						'type' => 'channel-property-message-consumer',
						'device' => [
							'identifier' => $entity->getDevice(),
						],
						'channel' => [
							'identifier' => $entity->getChannel(),
						],
						'property' => [
							'identifier' => $entity->getProperty(),
						],
					],
				);

				return true;
			}

			if (count($entity->getAttributes()) > 0) {
				$this->databaseHelper->transaction(function () use ($entity, $property): void {
					$toUpdate = $this->handlePropertyConfiguration($entity);

					$this->propertiesManager->update($property, Utils\ArrayHash::from($toUpdate));
				});
			}
		}

		$this->logger->debug(
			'Consumed channel property message',
			[
				'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
				'type' => 'channel-property-message-consumer',
				'device' => [
					'identifier' => $entity->getDevice(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
