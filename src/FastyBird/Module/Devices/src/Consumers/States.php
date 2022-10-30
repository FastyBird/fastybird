<?php declare(strict_types = 1);

/**
 * States.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Consumers
 * @since          0.1.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Module\Devices\Consumers;

use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Exchange\Entities as ExchangeEntities;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Utilities;
use IPub\Phone\Exceptions as PhoneExceptions;
use Nette\Utils;
use function array_merge;
use function in_array;

/**
 * States messages subscriber
 *
 * @package         FastyBird:DevicesModule!
 * @subpackage      Consumers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class States implements ExchangeConsumers\Consumer
{

	private const PROPERTIES_ACTIONS_ROUTING_KEYS = [
		MetadataTypes\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ACTION,
		MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ACTION,
		MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ACTION,
	];

	public function __construct(
		private readonly ExchangePublisher\Container $publisher,
		private readonly ExchangeEntities\EntityFactory $entityFactory,
		private readonly Models\Connectors\Properties\PropertiesRepository $connectorPropertiesRepository,
		private readonly Models\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		private readonly Models\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly Utilities\ConnectorPropertiesStates $connectorPropertiesStates,
		private readonly Utilities\DevicePropertiesStates $devicePropertiesStates,
		private readonly Utilities\ChannelPropertiesStates $channelPropertiesStates,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
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
	public function consume(
		MetadataTypes\TriggerSource|MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataEntities\Entity|null $entity,
	): void
	{
		if ($entity === null) {
			return;
		}

		if (in_array($routingKey->getValue(), self::PROPERTIES_ACTIONS_ROUTING_KEYS, true)) {
			if ($entity instanceof MetadataEntities\Actions\ActionConnectorProperty) {
				if ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::ACTION_SET)) {
					$findPropertyQuery = new Queries\FindConnectorProperties();
					$findPropertyQuery->byId($entity->getProperty());

					$property = $this->connectorPropertiesRepository->findOneBy($findPropertyQuery);

					if (!$property instanceof Entities\Connectors\Properties\Dynamic) {
						return;
					}

					$this->connectorPropertiesStates->setValue(
						$property,
						Utils\ArrayHash::from([
							'expectedValue' => $this->normalizeValue($property, $entity->getExpectedValue()),
							'pending' => true,
						]),
					);
				} elseif ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::ACTION_GET)) {
					$findPropertyQuery = new Queries\FindConnectorProperties();
					$findPropertyQuery->byId($entity->getProperty());

					$property = $this->connectorPropertiesRepository->findOneBy($findPropertyQuery);

					if ($property === null) {
						return;
					}

					$state = $property instanceof Entities\Connectors\Properties\Dynamic
						? $this->connectorPropertiesStates->getValue($property)
						: null;

					$publishRoutingKey = MetadataTypes\RoutingKey::get(
						MetadataTypes\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ENTITY_REPORTED,
					);

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
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
			} elseif ($entity instanceof MetadataEntities\Actions\ActionDeviceProperty) {
				if ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::ACTION_SET)) {
					$findPropertyQuery = new Queries\FindDeviceProperties();
					$findPropertyQuery->byId($entity->getProperty());

					$property = $this->devicePropertiesRepository->findOneBy($findPropertyQuery);

					if (
						!$property instanceof Entities\Devices\Properties\Dynamic
						&& !$property instanceof Entities\Devices\Properties\Mapped
					) {
						return;
					}

					$this->devicePropertiesStates->setValue(
						$property,
						Utils\ArrayHash::from([
							'expectedValue' => $this->normalizeValue($property, $entity->getExpectedValue()),
							'pending' => true,
						]),
					);
				} elseif ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::ACTION_GET)) {
					$findPropertyQuery = new Queries\FindDeviceProperties();
					$findPropertyQuery->byId($entity->getProperty());

					$property = $this->devicePropertiesRepository->findOneBy($findPropertyQuery);

					if ($property === null) {
						return;
					}

					$state = $property instanceof Entities\Devices\Properties\Dynamic
						|| $property instanceof Entities\Devices\Properties\Mapped
					 ? $this->devicePropertiesStates->getValue($property) : null;

					$publishRoutingKey = MetadataTypes\RoutingKey::get(
						MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_REPORTED,
					);

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
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
			} elseif ($entity instanceof MetadataEntities\Actions\ActionChannelProperty) {
				if ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::ACTION_SET)) {
					$findPropertyQuery = new Queries\FindChannelProperties();
					$findPropertyQuery->byId($entity->getProperty());

					$property = $this->channelPropertiesRepository->findOneBy($findPropertyQuery);

					if (
						!$property instanceof Entities\Channels\Properties\Dynamic
						&& !$property instanceof Entities\Channels\Properties\Mapped
					) {
						return;
					}

					$this->channelPropertiesStates->setValue(
						$property,
						Utils\ArrayHash::from([
							'expectedValue' => $this->normalizeValue($property, $entity->getExpectedValue()),
							'pending' => true,
						]),
					);
				} elseif ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::ACTION_GET)) {
					$findPropertyQuery = new Queries\FindChannelProperties();
					$findPropertyQuery->byId($entity->getProperty());

					$property = $this->channelPropertiesRepository->findOneBy($findPropertyQuery);

					if ($property === null) {
						return;
					}

					$state = $property instanceof Entities\Channels\Properties\Dynamic
						|| $property instanceof Entities\Channels\Properties\Mapped
					 ? $this->channelPropertiesStates->getValue($property) : null;

					$publishRoutingKey = MetadataTypes\RoutingKey::get(
						MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_REPORTED,
					);

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
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

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function normalizeValue(
		Entities\Property $property,
		float|bool|int|string|null $expectedValue,
	): float|bool|int|string|null
	{
		$valueToWrite = Utilities\ValueHelper::normalizeValue(
			$property->getDataType(),
			$expectedValue,
			$property->getFormat(),
			$property->getInvalid(),
		);

		if (
			$valueToWrite instanceof MetadataTypes\SwitchPayload
			&& $property->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
			&& $valueToWrite->equalsValue(MetadataTypes\SwitchPayload::PAYLOAD_TOGGLE)
			&& (
				$property instanceof Entities\Devices\Properties\Dynamic
				|| $property instanceof Entities\Devices\Properties\Mapped
				|| $property instanceof Entities\Channels\Properties\Dynamic
				|| $property instanceof Entities\Channels\Properties\Mapped
				|| $property instanceof Entities\Connectors\Properties\Dynamic
			)
		) {
			if ($property instanceof Entities\Connectors\Properties\Dynamic) {
				$state = $this->connectorPropertiesStates->getValue($property);
			} elseif (
				$property instanceof Entities\Devices\Properties\Dynamic
				|| $property instanceof Entities\Devices\Properties\Mapped
			) {
				$state = $this->devicePropertiesStates->getValue($property);
			} else {
				$state = $this->channelPropertiesStates->getValue($property);
			}

			if ($state !== null) {
				$valueToWrite = $state->getActualValue() === MetadataTypes\SwitchPayload::PAYLOAD_ON
					? MetadataTypes\SwitchPayload::get(MetadataTypes\SwitchPayload::PAYLOAD_OFF)
					: MetadataTypes\SwitchPayload::get(
						MetadataTypes\SwitchPayload::PAYLOAD_ON,
					);
			}
		}

		return Utilities\ValueHelper::flattenValue($valueToWrite);
	}

}
