<?php declare(strict_types = 1);

/**
 * DeviceStatus.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           18.07.23
 */

namespace FastyBird\Connector\NsPanel\Consumers\Messages;

use DateTimeInterface;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Consumers;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Library\Exchange\Entities as ExchangeEntities;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use IPub\Phone\Exceptions as PhoneExceptions;
use Nette;
use Nette\Utils;
use function assert;

/**
 * Device status message consumer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceStatus implements Consumers\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly bool $useExchange,
		private readonly Helpers\Property $propertyStateHelper,
		private readonly NsPanel\Logger $logger,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStateManager,
		private readonly ExchangeEntities\EntityFactory $entityFactory,
		private readonly ExchangePublisher\Publisher $publisher,
	)
	{
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws DevicesExceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 * @throws Utils\JsonException
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\DeviceStatus) {
			return false;
		}

		$findDeviceQuery = new Queries\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\NsPanelDevice::class);

		if ($device === null) {
			return true;
		}

		if ($device instanceof Entities\Devices\ThirdPartyDevice) {
			$this->processThirdPartyDevice($device, $entity->getStatuses());
		} elseif ($device instanceof Entities\Devices\SubDevice) {
			$this->processSubDevice($device, $entity->getStatuses());
		}

		$this->logger->debug(
			'Consumed device status message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'device-status-message-consumer',
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

	/**
	 * @param array<Entities\Messages\CapabilityStatus> $statuses
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function processSubDevice(
		Entities\Devices\SubDevice $device,
		array $statuses,
	): void
	{
		foreach ($statuses as $status) {
			$findChannelQuery = new Queries\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier(
				Helpers\Name::convertCapabilityToChannel($status->getCapability(), $status->getIdentifier()),
			);

			$channel = $this->channelsRepository->findOneBy(
				$findChannelQuery,
				Entities\NsPanelChannel::class,
			);

			if ($channel === null) {
				continue;
			}

			$findChannelPropertiesQuery = new DevicesQueries\FindChannelDynamicProperties();
			$findChannelPropertiesQuery->forChannel($channel);
			$findChannelPropertiesQuery->byIdentifier(Helpers\Name::convertProtocolToProperty($status->getProtocol()));

			$property = $this->channelsPropertiesRepository->findOneBy(
				$findChannelPropertiesQuery,
				DevicesEntities\Channels\Properties\Dynamic::class,
			);

			if ($property === null) {
				continue;
			}

			$this->propertyStateHelper->setValue($property, Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_KEY => Helpers\Transformer::transformValueFromDevice(
					$property->getDataType(),
					$property->getFormat(),
					$status->getValue(),
				),
				DevicesStates\Property::VALID_KEY => true,
			]));
		}
	}

	/**
	 * @param array<Entities\Messages\CapabilityStatus> $statuses
	 *
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws DevicesExceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 * @throws Utils\JsonException
	 */
	private function processThirdPartyDevice(
		Entities\Devices\ThirdPartyDevice $device,
		array $statuses,
	): void
	{
		foreach ($statuses as $status) {
			$findChannelQuery = new Queries\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier(
				Helpers\Name::convertCapabilityToChannel($status->getCapability(), $status->getIdentifier()),
			);

			$channel = $this->channelsRepository->findOneBy(
				$findChannelQuery,
				Entities\NsPanelChannel::class,
			);

			if ($channel === null) {
				continue;
			}

			$findChannelPropertiesQuery = new DevicesQueries\FindChannelProperties();
			$findChannelPropertiesQuery->forChannel($channel);
			$findChannelPropertiesQuery->byIdentifier(Helpers\Name::convertProtocolToProperty($status->getProtocol()));

			$property = $this->channelsPropertiesRepository->findOneBy($findChannelPropertiesQuery);

			if ($property === null) {
				continue;
			}

			assert(
				$property instanceof DevicesEntities\Channels\Properties\Dynamic
				|| $property instanceof DevicesEntities\Channels\Properties\Mapped
				|| $property instanceof DevicesEntities\Channels\Properties\Variable,
			);

			$value = Helpers\Transformer::transformValueFromDevice(
				$property->getDataType(),
				$property->getFormat(),
				$status->getValue(),
			);

			$this->writeProperty($device, $channel, $property, $value);
		}
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws DevicesExceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 * @throws Utils\JsonException
	 */
	private function writeProperty(
		Entities\Devices\ThirdPartyDevice $device,
		Entities\NsPanelChannel $channel,
		DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped|DevicesEntities\Channels\Properties\Variable $property,
		float|int|string|bool|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|DateTimeInterface|null $value,
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
					MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ACTION,
				),
				$this->entityFactory->create(
					Utils\Json::encode([
						'action' => MetadataTypes\PropertyAction::ACTION_SET,
						'device' => $device->getId()->toString(),
						'channel' => $channel->getId()->toString(),
						'property' => $property->getId()->toString(),
						'expected_value' => DevicesUtilities\ValueHelper::flattenValue($value),
					]),
					MetadataTypes\RoutingKey::get(
						MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ACTION,
					),
				),
			);
		} else {
			$this->channelPropertiesStateManager->writeValue(
				$property,
				Utils\ArrayHash::from([
					DevicesStates\Property::EXPECTED_VALUE_KEY => $value,
					DevicesStates\Property::PENDING_KEY => true,
				]),
			);
		}
	}

}
