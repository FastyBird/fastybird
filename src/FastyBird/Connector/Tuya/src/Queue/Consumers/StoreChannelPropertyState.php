<?php declare(strict_types = 1);

/**
 * StoreChannelPropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           04.09.22
 */

namespace FastyBird\Connector\Tuya\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Queue;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Library\Metadata\ValueObjects as MetadataValueObjects;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use function array_key_exists;
use function array_merge;
use function assert;
use function strval;

/**
 * Store channel property state message consumer
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreChannelPropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	/** @var array<string, Uuid\UuidInterface> */
	private array $dataPointsToProperties = [];

	public function __construct(
		private readonly Tuya\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStateManager,
		private readonly DevicesUtilities\Database $databaseHelper,
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
		if (!$entity instanceof Entities\Messages\StoreChannelPropertyState) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getIdentifier());

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'store-channel-property-state-message-consumer',
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

		foreach ($entity->getDataPoints() as $dataPoint) {
			$property = $this->findProperty(
				$entity->getConnector(),
				$entity->getIdentifier(),
				$dataPoint->getCode(),
			);

			if ($property !== null) {
				try {
					$valueToStore = MetadataUtilities\ValueHelper::normalizeValue(
						$property->getDataType(),
						$dataPoint->getValue(),
						$property->getFormat(),
						$property->getInvalid(),
					);

					$this->channelPropertiesStateManager->setValue($property, Utils\ArrayHash::from([
						DevicesStates\Property::ACTUAL_VALUE_FIELD => $valueToStore,
						DevicesStates\Property::VALID_FIELD => true,
					]));
				} catch (MetadataExceptions\InvalidArgument $ex) {
					$format = $property->getFormat();

					if (
						$property->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)
						&& $dataPoint->getValue() !== null
						&& $format instanceof MetadataValueObjects\StringEnumFormat
					) {
						$property = $this->databaseHelper->transaction(
							function () use ($dataPoint, $property, $format): DevicesEntities\Channels\Properties\Dynamic {
								$findPropertyQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
								$findPropertyQuery->byId($property->getId());

								$property = $this->channelsPropertiesRepository->findOneBy(
									$findPropertyQuery,
									DevicesEntities\Channels\Properties\Dynamic::class,
								);
								assert($property instanceof DevicesEntities\Channels\Properties\Dynamic);

								$updated = $this->channelsPropertiesManager->update($property, Utils\ArrayHash::from([
									'format' => array_merge(
										$format->toArray(),
										[Utils\Strings::lower(strval($dataPoint->getValue()))],
									),
								]));
								assert($updated instanceof DevicesEntities\Channels\Properties\Dynamic);

								return $updated;
							},
						);

						$valueToStore = MetadataUtilities\ValueHelper::normalizeValue(
							$property->getDataType(),
							$dataPoint->getValue(),
							$property->getFormat(),
							$property->getInvalid(),
						);

					} else {
						throw $ex;
					}
				}

				$this->channelPropertiesStateManager->setValue($property, Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_FIELD => $valueToStore,
					DevicesStates\Property::VALID_FIELD => true,
				]));
			}
		}

		$this->logger->debug(
			'Consumed store device state message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
				'type' => 'store-channel-property-state-message-consumer',
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function findProperty(
		Uuid\UuidInterface $connector,
		string $deviceIdentifier,
		string $dataPointIdentifier,
	): MetadataDocuments\DevicesModule\ChannelDynamicProperty|null
	{
		$key = $deviceIdentifier . '-' . $dataPointIdentifier;

		if (array_key_exists($key, $this->dataPointsToProperties)) {
			$findPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
			$findPropertyQuery->byId($this->dataPointsToProperties[$key]);

			$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
				$findPropertyQuery,
				MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
			);

			if ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
				return $property;
			}
		}

		$property = $this->loadProperty($connector, $deviceIdentifier, $dataPointIdentifier);

		if ($property !== null) {
			$this->dataPointsToProperties[$key] = $property->getId();
		}

		return $property;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function loadProperty(
		Uuid\UuidInterface $connector,
		string $deviceIdentifier,
		string $dataPointIdentifier,
	): MetadataDocuments\DevicesModule\ChannelDynamicProperty|null
	{
		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($connector);
		$findDeviceQuery->byIdentifier($deviceIdentifier);

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			return null;
		}

		$findChannelsQuery = new DevicesQueries\Configuration\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

		foreach ($channels as $channel) {
			$findPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
			$findPropertyQuery->forChannel($channel);

			foreach (
				$this->channelsPropertiesConfigurationRepository->findAllBy(
					$findPropertyQuery,
					MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
				) as $property
			) {
				if ($property->getIdentifier() === $dataPointIdentifier) {
					return $property;
				}
			}
		}

		return null;
	}

}
