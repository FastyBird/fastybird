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
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Utilities;
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
		private readonly ExchangePublisher\Publisher $publisher,
		private readonly ExchangeEntities\DocumentFactory $entityFactory,
		private readonly Models\Entities\Connectors\Properties\PropertiesRepository $connectorPropertiesRepository,
		private readonly Models\Entities\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		private readonly Models\Entities\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly Utilities\ConnectorPropertiesStates $connectorPropertiesStates,
		private readonly Utilities\DevicePropertiesStates $devicePropertiesStates,
		private readonly Utilities\ChannelPropertiesStates $channelPropertiesStates,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
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
				if ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::ACTION_SET)) {
					$findConnectorPropertyQuery = new Queries\Entities\FindConnectorProperties();
					$findConnectorPropertyQuery->byId($entity->getProperty());

					$property = $this->connectorPropertiesRepository->findOneBy($findConnectorPropertyQuery);

					if (!$property instanceof Entities\Connectors\Properties\Dynamic) {
						return;
					}

					$this->connectorPropertiesStates->writeValue(
						$property,
						Utils\ArrayHash::from([
							States\Property::EXPECTED_VALUE_KEY => $entity->getExpectedValue(),
							States\Property::PENDING_KEY => true,
						]),
					);
				} elseif ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::ACTION_GET)) {
					$findConnectorPropertyQuery = new Queries\Entities\FindConnectorProperties();
					$findConnectorPropertyQuery->byId($entity->getProperty());

					$property = $this->connectorPropertiesRepository->findOneBy($findConnectorPropertyQuery);

					if ($property === null) {
						return;
					}

					$state = $property instanceof Entities\Connectors\Properties\Dynamic
						? $this->connectorPropertiesStates->readValue($property)
						: null;

					$publishRoutingKey = MetadataTypes\RoutingKey::get(
						MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_DOCUMENT_REPORTED,
					);

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(
							Utils\Json::encode(
								array_merge(
									$state?->toArray() ?? [],
									$property->toArray(),
								),
							),
							$publishRoutingKey,
						),
					);
				}
			} elseif ($entity instanceof MetadataDocuments\Actions\ActionDeviceProperty) {
				if ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::ACTION_SET)) {
					$findConnectorPropertyQuery = new Queries\Entities\FindDeviceProperties();
					$findConnectorPropertyQuery->byId($entity->getProperty());

					$property = $this->devicePropertiesRepository->findOneBy($findConnectorPropertyQuery);

					if (
						!$property instanceof Entities\Devices\Properties\Dynamic
						&& !$property instanceof Entities\Devices\Properties\Mapped
					) {
						return;
					}

					$this->devicePropertiesStates->writeValue(
						$property,
						Utils\ArrayHash::from([
							States\Property::EXPECTED_VALUE_KEY => $entity->getExpectedValue(),
							States\Property::PENDING_KEY => true,
						]),
					);
				} elseif ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::ACTION_GET)) {
					$findConnectorPropertyQuery = new Queries\Entities\FindDeviceProperties();
					$findConnectorPropertyQuery->byId($entity->getProperty());

					$property = $this->devicePropertiesRepository->findOneBy($findConnectorPropertyQuery);

					if ($property === null) {
						return;
					}

					$state = $property instanceof Entities\Devices\Properties\Dynamic
						|| $property instanceof Entities\Devices\Properties\Mapped
					 ? $this->devicePropertiesStates->readValue($property) : null;

					$publishRoutingKey = MetadataTypes\RoutingKey::get(
						MetadataTypes\RoutingKey::DEVICE_PROPERTY_DOCUMENT_REPORTED,
					);

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(
							Utils\Json::encode(
								array_merge(
									$state?->toArray() ?? [],
									$property->toArray(),
								),
							),
							$publishRoutingKey,
						),
					);
				}
			} elseif ($entity instanceof MetadataDocuments\Actions\ActionChannelProperty) {
				if ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::ACTION_SET)) {
					$findConnectorPropertyQuery = new Queries\Entities\FindChannelProperties();
					$findConnectorPropertyQuery->byId($entity->getProperty());

					$property = $this->channelPropertiesRepository->findOneBy($findConnectorPropertyQuery);

					if (
						!$property instanceof Entities\Channels\Properties\Dynamic
						&& !$property instanceof Entities\Channels\Properties\Mapped
					) {
						return;
					}

					$this->channelPropertiesStates->writeValue(
						$property,
						Utils\ArrayHash::from([
							States\Property::EXPECTED_VALUE_KEY => $entity->getExpectedValue(),
							States\Property::PENDING_KEY => true,
						]),
					);
				} elseif ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::ACTION_GET)) {
					$findConnectorPropertyQuery = new Queries\Entities\FindChannelProperties();
					$findConnectorPropertyQuery->byId($entity->getProperty());

					$property = $this->channelPropertiesRepository->findOneBy($findConnectorPropertyQuery);

					if ($property === null) {
						return;
					}

					$state = $property instanceof Entities\Channels\Properties\Dynamic
						|| $property instanceof Entities\Channels\Properties\Mapped
					 ? $this->channelPropertiesStates->readValue($property) : null;

					$publishRoutingKey = MetadataTypes\RoutingKey::get(
						MetadataTypes\RoutingKey::CHANNEL_PROPERTY_DOCUMENT_REPORTED,
					);

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(
							Utils\Json::encode(
								array_merge(
									$state?->toArray() ?? [],
									$property->toArray(),
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
