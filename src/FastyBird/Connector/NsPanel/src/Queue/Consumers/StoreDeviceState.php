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
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Library\Exchange\Documents as ExchangeEntities;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
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
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStatesManager,
		private readonly ExchangeEntities\DocumentFactory $entityFactory,
		private readonly ExchangePublisher\Publisher $publisher,
	)
	{
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
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

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\NsPanelDevice::class);

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

		if ($device instanceof Entities\Devices\ThirdPartyDevice) {
			$this->processThirdPartyDevice($device, $entity->getState());
		} elseif ($device instanceof Entities\Devices\SubDevice) {
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
					'id' => $device->getId()->toString(),
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
		Entities\Devices\SubDevice $device,
		array $state,
	): void
	{
		foreach ($state as $item) {
			$findChannelQuery = new Queries\Entities\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier(
				Helpers\Name::convertCapabilityToChannel($item->getCapability(), $item->getIdentifier()),
			);

			$channel = $this->channelsRepository->findOneBy(
				$findChannelQuery,
				Entities\NsPanelChannel::class,
			);

			if ($channel === null) {
				continue;
			}

			$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
			$findChannelPropertiesQuery->forChannel($channel);
			$findChannelPropertiesQuery->byIdentifier(Helpers\Name::convertProtocolToProperty($item->getProtocol()));

			$property = $this->channelsPropertiesRepository->findOneBy(
				$findChannelPropertiesQuery,
				DevicesEntities\Channels\Properties\Dynamic::class,
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
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Utils\JsonException
	 */
	private function processThirdPartyDevice(
		Entities\Devices\ThirdPartyDevice $device,
		array $state,
	): void
	{
		foreach ($state as $item) {
			$findChannelQuery = new Queries\Entities\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier(
				Helpers\Name::convertCapabilityToChannel($item->getCapability(), $item->getIdentifier()),
			);

			$channel = $this->channelsRepository->findOneBy(
				$findChannelQuery,
				Entities\NsPanelChannel::class,
			);

			if ($channel === null) {
				continue;
			}

			$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertiesQuery->forChannel($channel);
			$findChannelPropertiesQuery->byIdentifier(Helpers\Name::convertProtocolToProperty($item->getProtocol()));

			$property = $this->channelsPropertiesRepository->findOneBy($findChannelPropertiesQuery);

			if ($property === null) {
				continue;
			}

			assert(
				$property instanceof DevicesEntities\Channels\Properties\Dynamic
				|| $property instanceof DevicesEntities\Channels\Properties\Mapped
				|| $property instanceof DevicesEntities\Channels\Properties\Variable,
			);

			$value = MetadataUtilities\ValueHelper::transformValueFromDevice(
				$property->getDataType(),
				$property->getFormat(),
				MetadataUtilities\ValueHelper::flattenValue($item->getValue()),
			);

			$this->writeProperty($device, $channel, $property, $value);
		}
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Utils\JsonException
	 */
	private function writeProperty(
		Entities\Devices\ThirdPartyDevice $device,
		Entities\NsPanelChannel $channel,
		DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped|DevicesEntities\Channels\Properties\Variable $property,
		float|int|string|bool|MetadataTypes\ButtonPayload|MetadataTypes\CoverPayload|MetadataTypes\SwitchPayload|DateTimeInterface|null $value,
	): void
	{
		if ($property instanceof DevicesEntities\Channels\Properties\Variable) {
			$this->channelsPropertiesManager->update(
				$property,
				Utils\ArrayHash::from([
					'value' => $value,
				]),
			);

			return;
		}

		if ($this->useExchange) {
			$this->publisher->publish(
				MetadataTypes\ModuleSource::get(
					MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
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
