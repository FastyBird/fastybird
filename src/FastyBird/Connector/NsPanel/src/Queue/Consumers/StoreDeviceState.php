<?php declare(strict_types = 1);

/**
 * StoreDeviceState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           18.07.23
 */

namespace FastyBird\Connector\NsPanel\Queue\Consumers;

use DateTimeInterface;
use Doctrine\DBAL;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Library\Exchange\Documents as ExchangeEntities;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
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
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDeviceState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly bool $useExchange,
		private readonly NsPanel\Logger $logger,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStatesManager,
		private readonly DevicesUtilities\Database $databaseHelper,
		private readonly ExchangeEntities\DocumentFactory $entityFactory,
		private readonly ExchangePublisher\Publisher $publisher,
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Utils\JsonException
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\StoreDeviceState) {
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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
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

		if ($device->getType() === Entities\Devices\ThirdPartyDevice::TYPE) {
			$this->processThirdPartyDevice($device, $entity->getState());
		} elseif ($device->getType() === Entities\Devices\SubDevice::TYPE) {
			$this->processSubDevice($device, $entity->getState());
		}

		$this->logger->debug(
			'Consumed store device state message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
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

	/**
	 * @param array<Entities\Messages\CapabilityState> $state
	 *
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function processSubDevice(
		MetadataDocuments\DevicesModule\Device $device,
		array $state,
	): void
	{
		foreach ($state as $item) {
			$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier(
				Helpers\Name::convertCapabilityToChannel($item->getCapability(), $item->getIdentifier()),
			);

			$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

			if ($channel === null) {
				continue;
			}

			$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
			$findChannelPropertiesQuery->forChannel($channel);
			$findChannelPropertiesQuery->byIdentifier(Helpers\Name::convertProtocolToProperty($item->getProtocol()));

			$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
				$findChannelPropertiesQuery,
				MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
			);

			if ($property === null) {
				continue;
			}

			$this->channelPropertiesStatesManager->writeValue(
				$property,
				Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_FIELD => MetadataUtilities\ValueHelper::transformValueFromDevice(
						$property->getDataType(),
						$property->getFormat(),
						MetadataUtilities\ValueHelper::flattenValue($item->getValue()),
					),
					DevicesStates\Property::VALID_FIELD => true,
				]),
			);
		}
	}

	/**
	 * @param array<Entities\Messages\CapabilityState> $state
	 *
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Utils\JsonException
	 */
	private function processThirdPartyDevice(
		MetadataDocuments\DevicesModule\Device $device,
		array $state,
	): void
	{
		foreach ($state as $item) {
			$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier(
				Helpers\Name::convertCapabilityToChannel($item->getCapability(), $item->getIdentifier()),
			);

			$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

			if ($channel === null) {
				continue;
			}

			$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelProperties();
			$findChannelPropertiesQuery->forChannel($channel);
			$findChannelPropertiesQuery->byIdentifier(Helpers\Name::convertProtocolToProperty($item->getProtocol()));

			$property = $this->channelsPropertiesConfigurationRepository->findOneBy($findChannelPropertiesQuery);

			if ($property === null) {
				continue;
			}

			$value = MetadataUtilities\ValueHelper::transformValueFromDevice(
				$property->getDataType(),
				$property->getFormat(),
				MetadataUtilities\ValueHelper::flattenValue($item->getValue()),
			);

			$this->writeThirdPartyProperty($device, $channel, $property, $value);
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Utils\JsonException
	 */
	private function writeThirdPartyProperty(
		MetadataDocuments\DevicesModule\Device $device,
		MetadataDocuments\DevicesModule\Channel $channel,
		MetadataDocuments\DevicesModule\ChannelProperty $property,
		float|int|string|bool|MetadataTypes\ButtonPayload|MetadataTypes\CoverPayload|MetadataTypes\SwitchPayload|DateTimeInterface|null $value,
	): void
	{
		if ($property instanceof MetadataDocuments\DevicesModule\ChannelVariableProperty) {
			$this->databaseHelper->transaction(
				function () use ($property, $value): void {
					$property = $this->channelsPropertiesRepository->find(
						$property->getId(),
						DevicesEntities\Channels\Properties\Variable::class,
					);
					assert($property instanceof DevicesEntities\Channels\Properties\Variable);

					$this->channelsPropertiesManager->update(
						$property,
						Utils\ArrayHash::from([
							'value' => $value,
						]),
					);
				},
			);

		} elseif ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
			$this->channelPropertiesStatesManager->writeValue(
				$property,
				Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_FIELD => MetadataUtilities\ValueHelper::transformValueFromDevice(
						$property->getDataType(),
						$property->getFormat(),
						MetadataUtilities\ValueHelper::flattenValue($value),
					),
					DevicesStates\Property::VALID_FIELD => true,
				]),
			);

		} elseif ($property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty) {
			if ($this->useExchange) {
				$this->publisher->publish(
					MetadataTypes\ConnectorSource::get(
						MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					),
					MetadataTypes\RoutingKey::get(
						MetadataTypes\RoutingKey::CHANNEL_PROPERTY_ACTION,
					),
					$this->entityFactory->create(
						Utils\Json::encode([
							'action' => MetadataTypes\PropertyAction::ACTION_SET,
							'device' => $device->getId()->toString(),
							'channel' => $channel->getId()->toString(),
							'property' => $property->getId()->toString(),
							'expected_value' => MetadataUtilities\ValueHelper::flattenValue($value),
						]),
						MetadataTypes\RoutingKey::get(
							MetadataTypes\RoutingKey::CHANNEL_PROPERTY_ACTION,
						),
					),
				);
			} else {
				$this->channelPropertiesStatesManager->writeValue(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::EXPECTED_VALUE_FIELD => $value,
						DevicesStates\Property::PENDING_FIELD => true,
					]),
				);
			}
		}
	}

}
