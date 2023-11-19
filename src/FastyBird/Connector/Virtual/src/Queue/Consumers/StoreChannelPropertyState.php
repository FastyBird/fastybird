<?php declare(strict_types = 1);

/**
 * StoreChannelPropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           04.09.22
 */

namespace FastyBird\Connector\Virtual\Queue\Consumers;

use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Helpers;
use FastyBird\Connector\Virtual\Queries;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Exchange\Documents as ExchangeEntities;
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
use Nette;
use Nette\Utils;
use Throwable;
use function is_string;

/**
 * Store channel property state message consumer
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreChannelPropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly bool $useExchange,
		private readonly Virtual\Logger $logger,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStateManager,
		private readonly ExchangeEntities\DocumentFactory $entityFactory,
		private readonly ExchangePublisher\Publisher $publisher,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\StoreChannelPropertyState) {
			return false;
		}

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byId($entity->getDevice());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\VirtualDevice::class);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
					'type' => 'store-channel-property-state-message-consumer',
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
						'id' => is_string($entity->getProperty())
							? $entity->getProperty()
							: $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$findChannelQuery = new DevicesQueries\Entities\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byId($entity->getChannel());

		$channel = $this->channelsRepository->findOneBy($findChannelQuery);

		if ($channel === null) {
			$this->logger->error(
				'Device channel could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
					'type' => 'store-channel-property-state-message-consumer',
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
						'id' => is_string($entity->getProperty())
							? $entity->getProperty()
							: $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$findPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findPropertyQuery->forChannel($channel);

		if (is_string($entity->getProperty())) {
			$findPropertyQuery->byIdentifier($entity->getProperty());
		} else {
			$findPropertyQuery->byId($entity->getProperty());
		}

		$property = $this->channelsPropertiesRepository->findOneBy($findPropertyQuery);

		if ($property === null) {
			$this->logger->error(
				'Device channel property could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
					'type' => 'store-channel-property-state-message-consumer',
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
						'id' => is_string($entity->getProperty())
							? $entity->getProperty()
							: $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$valueToStore = $entity->getValue();
		$valueToStore = DevicesUtilities\ValueHelper::normalizeValue(
			$property->getDataType(),
			$valueToStore,
			$property->getFormat(),
			$property->getInvalid(),
		);

		if ($property instanceof DevicesEntities\Channels\Properties\Mapped) {
			$valueToStore = Helpers\Transformer::toMappedParent($property, $valueToStore);
		}

		if ($property instanceof DevicesEntities\Channels\Properties\Variable) {
			$this->channelsPropertiesManager->update(
				$property,
				Utils\ArrayHash::from([
					'value' => DevicesUtilities\ValueHelper::flattenValue($valueToStore),
				]),
			);
		} elseif ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
			$this->channelPropertiesStateManager->setValue($property, Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_FIELD => DevicesUtilities\ValueHelper::flattenValue($valueToStore),
				DevicesStates\Property::VALID_FIELD => true,
			]));
		} elseif ($property instanceof DevicesEntities\Channels\Properties\Mapped) {
			if ($this->useExchange) {
				try {
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
								'expected_value' => DevicesUtilities\ValueHelper::flattenValue($valueToStore),
							]),
							MetadataTypes\RoutingKey::get(
								MetadataTypes\RoutingKey::CHANNEL_PROPERTY_ACTION,
							),
						),
					);
				} catch (Throwable $ex) {
					$this->logger->error(
						'Exchange message could not be created',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
							'type' => 'store-channel-property-state-message-consumer',
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
							'property' => [
								'id' => is_string($entity->getProperty())
									? $entity->getProperty()
									: $entity->getProperty()->toString(),
							],
							'data' => $entity->toArray(),
						],
					);

					return true;
				}
			} else {
				$this->channelPropertiesStateManager->writeValue($property, Utils\ArrayHash::from([
					DevicesStates\Property::EXPECTED_VALUE_FIELD => $entity->getValue(),
					DevicesStates\Property::PENDING_FIELD => true,
				]));
			}
		}

		$this->logger->debug(
			'Consumed device status message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
				'type' => 'store-channel-property-state-message-consumer',
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
