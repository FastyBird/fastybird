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
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Utilities;
use Nette\Utils;
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
		private readonly Models\DataStorage\ConnectorPropertiesRepository $connectorPropertiesRepository,
		private readonly Models\DataStorage\DevicePropertiesRepository $devicePropertiesRepository,
		private readonly Models\DataStorage\ChannelPropertiesRepository $channelPropertiesRepository,
		private readonly Utilities\ConnectorPropertiesStates $connectorPropertiesStates,
		private readonly Utilities\DevicePropertiesStates $devicePropertiesStates,
		private readonly Utilities\ChannelPropertiesStates $channelPropertiesStates,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
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
					$property = $this->connectorPropertiesRepository->findById($entity->getProperty());

					if (!$property instanceof MetadataEntities\DevicesModule\ConnectorDynamicProperty) {
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
					$property = $this->connectorPropertiesRepository->findById($entity->getProperty());

					if ($property === null) {
						return;
					}

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						MetadataTypes\RoutingKey::get(
							MetadataTypes\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ENTITY_REPORTED,
						),
						$property,
					);
				}
			} elseif ($entity instanceof MetadataEntities\Actions\ActionDeviceProperty) {
				if ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::ACTION_SET)) {
					$property = $this->devicePropertiesRepository->findById($entity->getProperty());

					if (
						!$property instanceof MetadataEntities\DevicesModule\DeviceDynamicProperty
						&& !$property instanceof MetadataEntities\DevicesModule\DeviceMappedProperty
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
					$property = $this->devicePropertiesRepository->findById($entity->getProperty());

					if ($property === null) {
						return;
					}

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						MetadataTypes\RoutingKey::get(
							MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_REPORTED,
						),
						$property,
					);
				}
			} elseif ($entity instanceof MetadataEntities\Actions\ActionChannelProperty) {
				if ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::ACTION_SET)) {
					$property = $this->channelPropertiesRepository->findById($entity->getProperty());

					if (
						!$property instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty
						&& !$property instanceof MetadataEntities\DevicesModule\ChannelMappedProperty
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
					$property = $this->channelPropertiesRepository->findById($entity->getProperty());

					if ($property === null) {
						return;
					}

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						MetadataTypes\RoutingKey::get(
							MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_REPORTED,
						),
						$property,
					);
				}
			}
		}
	}

	/**
	 * @throws MetadataExceptions\InvalidState
	 */
	private function normalizeValue(
		MetadataEntities\DevicesModule\ChannelMappedProperty|MetadataEntities\DevicesModule\ConnectorDynamicProperty|MetadataEntities\DevicesModule\DeviceMappedProperty|MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\ChannelDynamicProperty $property,
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
		) {
			$valueToWrite = $property->getActualValue() === MetadataTypes\SwitchPayload::PAYLOAD_ON
				? MetadataTypes\SwitchPayload::get(MetadataTypes\SwitchPayload::PAYLOAD_OFF)
				: MetadataTypes\SwitchPayload::get(
					MetadataTypes\SwitchPayload::PAYLOAD_ON,
				);
		}

		return Utilities\ValueHelper::flattenValue($valueToWrite);
	}

}
