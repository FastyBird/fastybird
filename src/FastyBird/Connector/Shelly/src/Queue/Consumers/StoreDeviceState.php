<?php declare(strict_types = 1);

/**
 * StoreDeviceState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           20.07.22
 */

namespace FastyBird\Connector\Shelly\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use function assert;

/**
 * Store device state message consumer
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDeviceState implements Queue\Consumer
{

	use Nette\SmartObject;
	use DeviceProperty;

	/**
	 * @param DevicesModels\Configuration\Devices\Repository<MetadataDocuments\DevicesModule\Device> $devicesConfigurationRepository
	 * @param DevicesModels\Configuration\Devices\Properties\Repository<MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceVariableProperty> $devicesPropertiesConfigurationRepository
	 * @param DevicesModels\Configuration\Channels\Repository<MetadataDocuments\DevicesModule\Channel> $channelsConfigurationRepository
	 * @param DevicesModels\Configuration\Channels\Properties\Repository<MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelVariableProperty> $channelsPropertiesConfigurationRepository
	 */
	public function __construct(
		protected readonly Shelly\Logger $logger,
		protected readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		protected readonly DevicesUtilities\Database $databaseHelper,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesUtilities\DevicePropertiesStates $devicePropertiesStateManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStateManager,
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
		if (!$entity instanceof Entities\Messages\StoreDeviceState) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->startWithIdentifier($entity->getIdentifier());

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'store-device-state-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'identifier' => $entity->getIdentifier(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$this->setDeviceProperty(
			$device->getId(),
			$entity->getIpAddress(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IP_ADDRESS,
		);

		foreach ($entity->getStates() as $state) {
			if ($state instanceof Entities\Messages\PropertyState) {
				$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($device);

				if (Utils\Strings::startsWith($state->getIdentifier(), '_')) {
					$findDevicePropertyQuery->endWithIdentifier($state->getIdentifier());
				} elseif (Utils\Strings::endsWith($state->getIdentifier(), '_')) {
					$findDevicePropertyQuery->startWithIdentifier($state->getIdentifier());
				} else {
					$findDevicePropertyQuery->byIdentifier($state->getIdentifier());
				}

				$property = $this->devicesPropertiesConfigurationRepository->findOneBy($findDevicePropertyQuery);

				if ($property !== null) {
					if ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
						$this->devicePropertiesStateManager->setValue($property, Utils\ArrayHash::from([
							DevicesStates\Property::ACTUAL_VALUE_FIELD => MetadataUtilities\ValueHelper::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$state->getValue(),
							),
							DevicesStates\Property::VALID_FIELD => true,
						]));

					} elseif ($property instanceof MetadataDocuments\DevicesModule\DeviceVariableProperty) {
						$this->databaseHelper->transaction(
							function () use ($property, $state): void {
								$findPropertyQuery = new DevicesQueries\Entities\FindDeviceVariableProperties();
								$findPropertyQuery->byId($property->getId());

								$property = $this->devicesPropertiesRepository->findOneBy(
									$findPropertyQuery,
									DevicesEntities\Devices\Properties\Variable::class,
								);
								assert($property instanceof DevicesEntities\Devices\Properties\Variable);

								$this->devicesPropertiesManager->update(
									$property,
									Utils\ArrayHash::from([
										'value' => MetadataUtilities\ValueHelper::transformValueFromDevice(
											$property->getDataType(),
											$property->getFormat(),
											$state->getValue(),
										),
									]),
								);
							},
						);
					}
				} else {
					$findChannelsQuery = new DevicesQueries\Configuration\FindChannels();
					$findChannelsQuery->forDevice($device);

					$channels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

					foreach ($channels as $channel) {
						$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
						$findChannelPropertyQuery->forChannel($channel);

						if (Utils\Strings::startsWith($state->getIdentifier(), '_')) {
							$findChannelPropertyQuery->endWithIdentifier($state->getIdentifier());
						} elseif (Utils\Strings::endsWith($state->getIdentifier(), '_')) {
							$findChannelPropertyQuery->startWithIdentifier($state->getIdentifier());
						} else {
							$findChannelPropertyQuery->byIdentifier($state->getIdentifier());
						}

						$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
							$findChannelPropertyQuery,
						);

						if ($property !== null) {
							if ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
								$this->channelPropertiesStateManager->setValue($property, Utils\ArrayHash::from([
									DevicesStates\Property::ACTUAL_VALUE_FIELD => MetadataUtilities\ValueHelper::transformValueFromDevice(
										$property->getDataType(),
										$property->getFormat(),
										$state->getValue(),
									),
									DevicesStates\Property::VALID_FIELD => true,
								]));

							} elseif ($property instanceof MetadataDocuments\DevicesModule\ChannelVariableProperty) {
								$this->databaseHelper->transaction(
									function () use ($property, $state): void {
										$findPropertyQuery = new DevicesQueries\Entities\FindChannelVariableProperties();
										$findPropertyQuery->byId($property->getId());

										$property = $this->channelsPropertiesRepository->findOneBy(
											$findPropertyQuery,
											DevicesEntities\Channels\Properties\Variable::class,
										);
										assert($property instanceof DevicesEntities\Channels\Properties\Variable);

										$this->channelsPropertiesManager->update(
											$property,
											Utils\ArrayHash::from([
												'value' => MetadataUtilities\ValueHelper::transformValueFromDevice(
													$property->getDataType(),
													$property->getFormat(),
													$state->getValue(),
												),
											]),
										);
									},
								);
							}

							break;
						}
					}
				}
			} else {
				$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
				$findChannelQuery->forDevice($device);

				if (Utils\Strings::startsWith($state->getIdentifier(), '_')) {
					$findChannelQuery->endWithIdentifier($state->getIdentifier());
				} elseif (Utils\Strings::endsWith($state->getIdentifier(), '_')) {
					$findChannelQuery->startWithIdentifier($state->getIdentifier());
				} else {
					$findChannelQuery->byIdentifier($state->getIdentifier());
				}

				$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

				if ($channel !== null) {
					foreach ($state->getSensors() as $sensor) {
						$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
						$findChannelPropertyQuery->forChannel($channel);

						if (Utils\Strings::startsWith($sensor->getIdentifier(), '_')) {
							$findChannelPropertyQuery->endWithIdentifier($sensor->getIdentifier());
						} elseif (Utils\Strings::endsWith($sensor->getIdentifier(), '_')) {
							$findChannelPropertyQuery->startWithIdentifier($sensor->getIdentifier());
						} else {
							$findChannelPropertyQuery->byIdentifier($sensor->getIdentifier());
						}

						$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
							$findChannelPropertyQuery,
						);

						if ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
							$this->channelPropertiesStateManager->setValue($property, Utils\ArrayHash::from([
								DevicesStates\Property::ACTUAL_VALUE_FIELD => MetadataUtilities\ValueHelper::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$sensor->getValue(),
								),
								DevicesStates\Property::VALID_FIELD => true,
							]));
						} elseif ($property instanceof MetadataDocuments\DevicesModule\ChannelVariableProperty) {
							$this->databaseHelper->transaction(
								function () use ($property, $sensor): void {
									$findPropertyQuery = new DevicesQueries\Entities\FindChannelVariableProperties();
									$findPropertyQuery->byId($property->getId());

									$property = $this->channelsPropertiesRepository->findOneBy(
										$findPropertyQuery,
										DevicesEntities\Channels\Properties\Variable::class,
									);
									assert($property instanceof DevicesEntities\Channels\Properties\Variable);

									$this->channelsPropertiesManager->update(
										$property,
										Utils\ArrayHash::from([
											'value' => MetadataUtilities\ValueHelper::transformValueFromDevice(
												$property->getDataType(),
												$property->getFormat(),
												$sensor->getValue(),
											),
										]),
									);
								},
							);
						}
					}
				}
			}
		}

		$this->logger->debug(
			'Consumed device status message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
				'type' => 'store-device-state-message-consumer',
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
