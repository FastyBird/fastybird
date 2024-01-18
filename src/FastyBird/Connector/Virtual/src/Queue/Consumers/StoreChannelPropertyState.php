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

use Doctrine\DBAL;
use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Exchange\Documents as ExchangeEntities;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use function array_merge;
use function assert;
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
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesUtilities\Database $databaseHelper,
		private readonly DevicesModels\States\ChannelPropertiesManager $channelPropertiesStatesManager,
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
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\StoreChannelPropertyState) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byId($entity->getDevice());

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_VIRTUAL,
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
					'property' => array_merge(
						is_string($entity->getProperty()) ? ['identifier' => $entity->getProperty()] : [],
						!is_string($entity->getProperty()) ? ['id' => $entity->getProperty()->toString()] : [],
					),
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byId($entity->getChannel());

		$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

		if ($channel === null) {
			$this->logger->error(
				'Device channel could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_VIRTUAL,
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
					'property' => array_merge(
						is_string($entity->getProperty()) ? ['identifier' => $entity->getProperty()] : [],
						!is_string($entity->getProperty()) ? ['id' => $entity->getProperty()->toString()] : [],
					),
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
		$findChannelPropertyQuery->forChannel($channel);
		if (is_string($entity->getProperty())) {
			$findChannelPropertyQuery->byIdentifier($entity->getProperty());
		} else {
			$findChannelPropertyQuery->byId($entity->getProperty());
		}

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy($findChannelPropertyQuery);

		if ($property === null) {
			$this->logger->error(
				'Device channel property could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_VIRTUAL,
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
					'property' => array_merge(
						is_string($entity->getProperty()) ? ['identifier' => $entity->getProperty()] : [],
						!is_string($entity->getProperty()) ? ['id' => $entity->getProperty()->toString()] : [],
					),
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		if ($property instanceof MetadataDocuments\DevicesModule\ChannelVariableProperty) {
			$this->databaseHelper->transaction(
				function () use ($entity, $property): void {
					$property = $this->channelsPropertiesRepository->find(
						$property->getId(),
						DevicesEntities\Channels\Properties\Variable::class,
					);
					assert($property instanceof DevicesEntities\Channels\Properties\Variable);

					$this->channelsPropertiesManager->update(
						$property,
						Utils\ArrayHash::from([
							'value' => $entity->getValue(),
						]),
					);
				},
			);

		} elseif ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
			$this->channelPropertiesStatesManager->set(
				$property,
				Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_FIELD => $entity->getValue(),
				]),
			);
		} elseif ($property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty) {
			$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
			$findChannelPropertyQuery->byId($property->getParent());

			$parent = $this->channelsPropertiesConfigurationRepository->findOneBy($findChannelPropertyQuery);

			if ($parent instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
				try {
					if ($this->useExchange) {
						$this->publisher->publish(
							MetadataTypes\ConnectorSource::get(
								MetadataTypes\ConnectorSource::CONNECTOR_VIRTUAL,
							),
							MetadataTypes\RoutingKey::get(
								MetadataTypes\RoutingKey::CHANNEL_PROPERTY_ACTION,
							),
							$this->entityFactory->create(
								Utils\Json::encode([
									'action' => MetadataTypes\PropertyAction::SET,
									'device' => $device->getId()->toString(),
									'channel' => $channel->getId()->toString(),
									'property' => $property->getId()->toString(),
									'expected_value' => MetadataUtilities\Value::flattenValue(
										$this->channelPropertiesStatesManager->normalizePublishValue(
											$property,
											$entity->getValue(),
										),
									),
								]),
								MetadataTypes\RoutingKey::get(
									MetadataTypes\RoutingKey::CHANNEL_PROPERTY_ACTION,
								),
							),
						);
					} else {
						$this->channelPropertiesStatesManager->write(
							$property,
							Utils\ArrayHash::from([
								DevicesStates\Property::EXPECTED_VALUE_FIELD => $entity->getValue(),
							]),
						);
					}
				} catch (DevicesExceptions\InvalidState | Utils\JsonException | MetadataExceptions\InvalidValue $ex) {
					$this->logger->warning(
						'State value could not be converted to mapped parent',
						[
							'source' => MetadataTypes\ConnectorSource::CONNECTOR_VIRTUAL,
							'type' => 'store-channel-property-state-message-consumer',
							'exception' => ApplicationHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $entity->getConnector()->toString(),
							],
							'device' => [
								'id' => $entity->getDevice()->toString(),
							],
							'channel' => [
								'id' => $entity->getChannel()->toString(),
							],
							'property' => array_merge(
								is_string($entity->getProperty()) ? ['identifier' => $entity->getProperty()] : [],
								!is_string($entity->getProperty()) ? ['id' => $entity->getProperty()->toString()] : [],
							),
							'data' => $entity->toArray(),
						],
					);

					return true;
				}
			} elseif ($parent instanceof MetadataDocuments\DevicesModule\ChannelVariableProperty) {
				$this->databaseHelper->transaction(function () use ($entity, $parent): void {
					$property = $this->channelsPropertiesRepository->find(
						$parent->getId(),
						DevicesEntities\Channels\Properties\Variable::class,
					);

					if ($property !== null) {
						$this->channelsPropertiesManager->update(
							$property,
							Utils\ArrayHash::from([
								'value' => $entity->getValue(),
							]),
						);
					} else {
						$this->logger->error(
							'Mapped variable property could not be updated',
							[
								'source' => MetadataTypes\ConnectorSource::CONNECTOR_VIRTUAL,
								'type' => 'characteristics-controller',
								'connector' => [
									'id' => $entity->getConnector()->toString(),
								],
								'device' => [
									'id' => $entity->getDevice()->toString(),
								],
								'channel' => [
									'id' => $entity->getChannel()->toString(),
								],
								'property' => array_merge(
									is_string($entity->getProperty()) ? ['identifier' => $entity->getProperty()] : [],
									!is_string(
										$entity->getProperty(),
									) ? ['id' => $entity->getProperty()->toString()] : [],
								),
								'data' => $entity->toArray(),
							],
						);
					}
				});
			}
		}

		$this->logger->debug(
			'Consumed store device state message',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_VIRTUAL,
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
				'property' => array_merge(
					is_string($entity->getProperty()) ? ['identifier' => $entity->getProperty()] : [],
					!is_string($entity->getProperty()) ? ['id' => $entity->getProperty()->toString()] : [],
				),
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
