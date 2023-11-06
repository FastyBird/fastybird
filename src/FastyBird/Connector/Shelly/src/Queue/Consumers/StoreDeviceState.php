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
use FastyBird\Connector\Shelly\Queries;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;

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

	public function __construct(
		protected readonly Shelly\Logger $logger,
		protected readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		protected readonly DevicesUtilities\Database $databaseHelper,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesUtilities\DevicePropertiesStates $devicePropertiesStateManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStateManager,
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\StoreDeviceState) {
			return false;
		}

		$findDeviceQuery = new Queries\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->startWithIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\ShellyDevice::class);

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
				$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($device);

				if (Utils\Strings::startsWith($state->getIdentifier(), '_')) {
					$findDevicePropertyQuery->endWithIdentifier($state->getIdentifier());
				} elseif (Utils\Strings::endsWith($state->getIdentifier(), '_')) {
					$findDevicePropertyQuery->startWithIdentifier($state->getIdentifier());
				} else {
					$findDevicePropertyQuery->byIdentifier($state->getIdentifier());
				}

				$property = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

				if ($property !== null) {
					if ($property instanceof DevicesEntities\Devices\Properties\Dynamic) {
						$this->devicePropertiesStateManager->setValue($property, Utils\ArrayHash::from([
							DevicesStates\Property::ACTUAL_VALUE_KEY => DevicesUtilities\ValueHelper::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$state->getValue(),
							),
							DevicesStates\Property::VALID_KEY => true,
						]));

					} elseif ($property instanceof DevicesEntities\Devices\Properties\Variable) {
						$this->databaseHelper->transaction(
							function () use ($property, $state): void {
								$this->devicesPropertiesManager->update(
									$property,
									Utils\ArrayHash::from([
										'value' => DevicesUtilities\ValueHelper::transformValueFromDevice(
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
					$findChannelsQuery = new DevicesQueries\FindChannels();
					$findChannelsQuery->forDevice($device);

					$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

					foreach ($channels as $channel) {
						$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
						$findChannelPropertyQuery->forChannel($channel);

						if (Utils\Strings::startsWith($state->getIdentifier(), '_')) {
							$findChannelPropertyQuery->endWithIdentifier($state->getIdentifier());
						} elseif (Utils\Strings::endsWith($state->getIdentifier(), '_')) {
							$findChannelPropertyQuery->startWithIdentifier($state->getIdentifier());
						} else {
							$findChannelPropertyQuery->byIdentifier($state->getIdentifier());
						}

						$property = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

						if ($property !== null) {
							if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
								$this->channelPropertiesStateManager->setValue($property, Utils\ArrayHash::from([
									DevicesStates\Property::ACTUAL_VALUE_KEY => DevicesUtilities\ValueHelper::transformValueFromDevice(
										$property->getDataType(),
										$property->getFormat(),
										$state->getValue(),
									),
									DevicesStates\Property::VALID_KEY => true,
								]));

							} elseif ($property instanceof DevicesEntities\Channels\Properties\Variable) {
								$this->databaseHelper->transaction(
									function () use ($property, $state): void {
										$this->channelsPropertiesManager->update(
											$property,
											Utils\ArrayHash::from([
												'value' => DevicesUtilities\ValueHelper::transformValueFromDevice(
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
				$findChannelQuery = new DevicesQueries\FindChannels();
				$findChannelQuery->forDevice($device);

				if (Utils\Strings::startsWith($state->getIdentifier(), '_')) {
					$findChannelQuery->endWithIdentifier($state->getIdentifier());
				} elseif (Utils\Strings::endsWith($state->getIdentifier(), '_')) {
					$findChannelQuery->startWithIdentifier($state->getIdentifier());
				} else {
					$findChannelQuery->byIdentifier($state->getIdentifier());
				}

				$channel = $this->channelsRepository->findOneBy($findChannelQuery);

				if ($channel !== null) {
					foreach ($state->getSensors() as $sensor) {
						$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
						$findChannelPropertyQuery->forChannel($channel);

						if (Utils\Strings::startsWith($sensor->getIdentifier(), '_')) {
							$findChannelPropertyQuery->endWithIdentifier($sensor->getIdentifier());
						} elseif (Utils\Strings::endsWith($sensor->getIdentifier(), '_')) {
							$findChannelPropertyQuery->startWithIdentifier($sensor->getIdentifier());
						} else {
							$findChannelPropertyQuery->byIdentifier($sensor->getIdentifier());
						}

						$property = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

						if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
							$this->channelPropertiesStateManager->setValue($property, Utils\ArrayHash::from([
								DevicesStates\Property::ACTUAL_VALUE_KEY => DevicesUtilities\ValueHelper::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$sensor->getValue(),
								),
								DevicesStates\Property::VALID_KEY => true,
							]));
						} elseif ($property instanceof DevicesEntities\Channels\Properties\Variable) {
							$this->databaseHelper->transaction(
								function () use ($property, $sensor): void {
									$this->channelsPropertiesManager->update(
										$property,
										Utils\ArrayHash::from([
											'value' => DevicesUtilities\ValueHelper::transformValueFromDevice(
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
