<?php declare(strict_types = 1);

/**
 * State.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Module\Devices\Consumers;

use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Exchange\Documents as ExchangeEntities;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
use Nette\Utils;
use function array_merge;
use function in_array;

/**
 * States messages subscriber
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class State implements ExchangeConsumers\Consumer
{

	private const PROPERTIES_ACTIONS_ROUTING_KEYS = [
		MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_ACTION,
		MetadataTypes\RoutingKey::DEVICE_PROPERTY_ACTION,
		MetadataTypes\RoutingKey::CHANNEL_PROPERTY_ACTION,
	];

	public function __construct(
		private readonly Devices\Logger $logger,
		private readonly ExchangePublisher\Publisher $publisher,
		private readonly ExchangeEntities\DocumentFactory $entityFactory,
		private readonly Models\Configuration\Connectors\Properties\Repository $connectorPropertiesConfigurationRepository,
		private readonly Models\Configuration\Devices\Properties\Repository $devicePropertiesConfigurationRepository,
		private readonly Models\Configuration\Channels\Properties\Repository $channelPropertiesConfigurationRepository,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Models\States\ChannelPropertiesManager $channelPropertiesStatesManager,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws Utils\JsonException
	 */
	public function consume(
		MetadataTypes\AutomatorSource|MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataDocuments\Document|null $entity,
	): void
	{
		if ($entity === null) {
			return;
		}

		if (in_array($routingKey->getValue(), self::PROPERTIES_ACTIONS_ROUTING_KEYS, true)) {
			if ($entity instanceof MetadataDocuments\Actions\ActionConnectorProperty) {
				if ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::SET)) {
					$findConnectorPropertyQuery = new Queries\Configuration\FindConnectorDynamicProperties();
					$findConnectorPropertyQuery->byId($entity->getProperty());

					$property = $this->connectorPropertiesConfigurationRepository->findOneBy(
						$findConnectorPropertyQuery,
						MetadataDocuments\DevicesModule\ConnectorDynamicProperty::class,
					);

					if ($property === null) {
						return;
					}

					$this->connectorPropertiesStatesManager->write(
						$property,
						Utils\ArrayHash::from([
							States\Property::EXPECTED_VALUE_FIELD => $entity->getExpectedValue(),
						]),
					);

					$this->logger->info('Requested write value to connector property', [
						'source' => MetadataTypes\ModuleSource::DEVICES,
						'type' => 'state-consumer',
						'connector' => [
							'id' => $entity->getConnector()->toString(),
						],
						'property' => [
							'id' => $property->getId()->toString(),
							'identifier' => $property->getIdentifier(),
						],
						'expected_value' => $entity->getExpectedValue(),
						'message' => [
							'routing_key' => $routingKey->getValue(),
							'source' => $source->getValue(),
							'data' => $entity->toArray(),
						],
					]);
				} elseif ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::GET)) {
					$findConnectorPropertyQuery = new Queries\Configuration\FindConnectorDynamicProperties();
					$findConnectorPropertyQuery->byId($entity->getProperty());

					$property = $this->connectorPropertiesConfigurationRepository->findOneBy(
						$findConnectorPropertyQuery,
						MetadataDocuments\DevicesModule\ConnectorDynamicProperty::class,
					);

					if ($property === null) {
						return;
					}

					$state = $this->connectorPropertiesStatesManager->read($property);

					$publishRoutingKey = MetadataTypes\RoutingKey::get(
						MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_DOCUMENT_REPORTED,
					);

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(
							Utils\Json::encode(
								array_merge(
									$property->toArray(),
									$state?->toArray() ?? [],
								),
							),
							$publishRoutingKey,
						),
					);
				}
			} elseif ($entity instanceof MetadataDocuments\Actions\ActionDeviceProperty) {
				if ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::SET)) {
					$findConnectorPropertyQuery = new Queries\Configuration\FindDeviceProperties();
					$findConnectorPropertyQuery->byId($entity->getProperty());

					$property = $this->devicePropertiesConfigurationRepository->findOneBy($findConnectorPropertyQuery);

					if (
						!$property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty
						&& !$property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty
					) {
						return;
					}

					$this->devicePropertiesStatesManager->write(
						$property,
						Utils\ArrayHash::from([
							States\Property::EXPECTED_VALUE_FIELD => $entity->getExpectedValue(),
						]),
					);

					$this->logger->info('Requested write value to device property', [
						'source' => MetadataTypes\ModuleSource::DEVICES,
						'type' => 'state-consumer',
						'device' => [
							'id' => $entity->getDevice()->toString(),
						],
						'property' => [
							'id' => $property->getId()->toString(),
							'identifier' => $property->getIdentifier(),
						],
						'expected_value' => $entity->getExpectedValue(),
						'message' => [
							'routing_key' => $routingKey->getValue(),
							'source' => $source->getValue(),
							'data' => $entity->toArray(),
						],
					]);
				} elseif ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::GET)) {
					$findConnectorPropertyQuery = new Queries\Configuration\FindDeviceProperties();
					$findConnectorPropertyQuery->byId($entity->getProperty());

					$property = $this->devicePropertiesConfigurationRepository->findOneBy($findConnectorPropertyQuery);

					if (
						!$property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty
						&& !$property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty
					) {
						return;
					}

					$state = $this->devicePropertiesStatesManager->read($property);

					$publishRoutingKey = MetadataTypes\RoutingKey::get(
						MetadataTypes\RoutingKey::DEVICE_PROPERTY_DOCUMENT_REPORTED,
					);

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(
							Utils\Json::encode(
								array_merge(
									$property->toArray(),
									$state?->toArray() ?? [],
								),
							),
							$publishRoutingKey,
						),
					);
				}
			} elseif ($entity instanceof MetadataDocuments\Actions\ActionChannelProperty) {
				if ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::SET)) {
					$findConnectorPropertyQuery = new Queries\Configuration\FindChannelProperties();
					$findConnectorPropertyQuery->byId($entity->getProperty());

					$property = $this->channelPropertiesConfigurationRepository->findOneBy($findConnectorPropertyQuery);

					if (
						!$property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
						&& !$property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty
					) {
						return;
					}

					$this->channelPropertiesStatesManager->write(
						$property,
						Utils\ArrayHash::from([
							States\Property::EXPECTED_VALUE_FIELD => $entity->getExpectedValue(),
						]),
					);

					$this->logger->info('Requested write value to channel property', [
						'source' => MetadataTypes\ModuleSource::DEVICES,
						'type' => 'state-consumer',
						'channel' => [
							'id' => $entity->getChannel()->toString(),
						],
						'property' => [
							'id' => $property->getId()->toString(),
							'identifier' => $property->getIdentifier(),
						],
						'expected_value' => $entity->getExpectedValue(),
						'message' => [
							'routing_key' => $routingKey->getValue(),
							'source' => $source->getValue(),
							'data' => $entity->toArray(),
						],
					]);
				} elseif ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::GET)) {
					$findConnectorPropertyQuery = new Queries\Configuration\FindChannelProperties();
					$findConnectorPropertyQuery->byId($entity->getProperty());

					$property = $this->channelPropertiesConfigurationRepository->findOneBy($findConnectorPropertyQuery);

					if (
						!$property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
						&& !$property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty
					) {
						return;
					}

					$state = $this->channelPropertiesStatesManager->read($property);

					$publishRoutingKey = MetadataTypes\RoutingKey::get(
						MetadataTypes\RoutingKey::CHANNEL_PROPERTY_DOCUMENT_REPORTED,
					);

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(
							Utils\Json::encode(
								array_merge(
									$property->toArray(),
									$state?->toArray() ?? [],
								),
							),
							$publishRoutingKey,
						),
					);
				}
			}
		}
	}

}
