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

use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
use Nette\Utils;
use Throwable;
use function in_array;
use function React\Async\await;

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
		private readonly Models\Configuration\Connectors\Properties\Repository $connectorPropertiesConfigurationRepository,
		private readonly Models\Configuration\Devices\Properties\Repository $devicePropertiesConfigurationRepository,
		private readonly Models\Configuration\Channels\Properties\Repository $channelPropertiesConfigurationRepository,
		private readonly Models\States\Async\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly Models\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Models\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly ExchangePublisher\Async\Publisher $publisher,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\MalformedInput
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

					$result = null;
					$data = [];

					if ($entity->getSet() !== null) {
						if ($entity->getSet()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
							$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getSet()->getActualValue();
						}

						if ($entity->getSet()->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
							$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getSet()->getExpectedValue();
						}

						if ($data !== []) {
							$result = $this->connectorPropertiesStatesManager->writeState(
								$property,
								Utils\ArrayHash::from($data),
								false,
								$source,
							);
						}
					} elseif ($entity->getWrite() !== null) {
						if ($entity->getWrite()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
							$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getWrite()->getActualValue();
						}

						if ($entity->getWrite()->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
							$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getWrite()->getExpectedValue();
						}

						if ($data !== []) {
							$result = $this->connectorPropertiesStatesManager->writeState(
								$property,
								Utils\ArrayHash::from($data),
								true,
								$source,
							);
						}
					}

					$result
						?->then(function () use ($entity, $property, $source, $routingKey, $data): void {
							$this->logger->debug(
								'Requested write value to connector property',
								[
									'source' => MetadataTypes\ModuleSource::DEVICES,
									'type' => 'state-consumer',
									'connector' => [
										'id' => $entity->getConnector()->toString(),
									],
									'property' => [
										'id' => $property->getId()->toString(),
										'identifier' => $property->getIdentifier(),
									],
									'data' => $data,
									'message' => [
										'routing_key' => $routingKey->getValue(),
										'source' => $source->getValue(),
										'data' => $entity->toArray(),
									],
								],
							);
						});
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

					$state = await($this->connectorPropertiesStatesManager->readState($property));

					if ($state === null) {
						return;
					}

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
						MetadataTypes\RoutingKey::get(
							MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_STATE_DOCUMENT_REPORTED,
						),
						$state,
					)
						->then(function () use ($entity, $property, $source, $routingKey): void {
							$this->logger->debug(
								'Requested write value to channel property',
								[
									'source' => MetadataTypes\ModuleSource::DEVICES,
									'type' => 'state-consumer',
									'connector' => [
										'id' => $entity->getConnector()->toString(),
									],
									'property' => [
										'id' => $property->getId()->toString(),
										'identifier' => $property->getIdentifier(),
									],
									'message' => [
										'routing_key' => $routingKey->getValue(),
										'source' => $source->getValue(),
										'data' => $entity->toArray(),
									],
								],
							);
						})
						->catch(function (Throwable $ex): void {
							$this->logger->error(
								'Requested action could not be published for write action',
								[
									'source' => MetadataTypes\ModuleSource::DEVICES,
									'type' => 'channel-properties-states',
									'exception' => ApplicationHelpers\Logger::buildException($ex),
								],
							);
						});
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

					$result = null;
					$data = [];

					if ($entity->getSet() !== null) {
						if ($entity->getSet()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
							$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getSet()->getActualValue();
						}

						if ($entity->getSet()->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
							$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getSet()->getExpectedValue();
						}

						if ($data !== []) {
							$result = $this->devicePropertiesStatesManager->writeState(
								$property,
								Utils\ArrayHash::from($data),
								false,
								$source,
							);
						}
					} elseif ($entity->getWrite() !== null) {
						if ($entity->getWrite()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
							$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getWrite()->getActualValue();
						}

						if ($entity->getWrite()->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
							$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getWrite()->getExpectedValue();
						}

						if ($data !== []) {
							$result = $this->devicePropertiesStatesManager->writeState(
								$property,
								Utils\ArrayHash::from($data),
								true,
								$source,
							);
						}
					}

					$result
						?->then(function () use ($entity, $property, $source, $routingKey, $data): void {
							$this->logger->debug(
								'Requested write value to device property',
								[
									'source' => MetadataTypes\ModuleSource::DEVICES,
									'type' => 'state-consumer',
									'device' => [
										'id' => $entity->getDevice()->toString(),
									],
									'property' => [
										'id' => $property->getId()->toString(),
										'identifier' => $property->getIdentifier(),
									],
									'data' => $data,
									'message' => [
										'routing_key' => $routingKey->getValue(),
										'source' => $source->getValue(),
										'data' => $entity->toArray(),
									],
								],
							);
						});
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

					$state = await($this->devicePropertiesStatesManager->readState($property));

					if ($state === null) {
						return;
					}

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
						MetadataTypes\RoutingKey::get(
							MetadataTypes\RoutingKey::DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED,
						),
						$state,
					)
						->then(function () use ($entity, $property, $source, $routingKey): void {
							$this->logger->debug(
								'Requested write value to channel property',
								[
									'source' => MetadataTypes\ModuleSource::DEVICES,
									'type' => 'state-consumer',
									'device' => [
										'id' => $entity->getDevice()->toString(),
									],
									'property' => [
										'id' => $property->getId()->toString(),
										'identifier' => $property->getIdentifier(),
									],
									'message' => [
										'routing_key' => $routingKey->getValue(),
										'source' => $source->getValue(),
										'data' => $entity->toArray(),
									],
								],
							);
						})
						->catch(function (Throwable $ex): void {
							$this->logger->error(
								'Requested action could not be published for write action',
								[
									'source' => MetadataTypes\ModuleSource::DEVICES,
									'type' => 'channel-properties-states',
									'exception' => ApplicationHelpers\Logger::buildException($ex),
								],
							);
						});
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

					$result = null;
					$data = [];

					if ($entity->getSet() !== null) {
						if ($entity->getSet()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
							$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getSet()->getActualValue();
						}

						if ($entity->getSet()->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
							$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getSet()->getExpectedValue();
						}

						if ($data !== []) {
							$result = $this->channelPropertiesStatesManager->writeState(
								$property,
								Utils\ArrayHash::from($data),
								false,
								$source,
							);
						}
					} elseif ($entity->getWrite() !== null) {
						if ($entity->getWrite()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
							$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getWrite()->getActualValue();
						}

						if ($entity->getWrite()->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
							$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getWrite()->getExpectedValue();
						}

						if ($data !== []) {
							$result = $this->channelPropertiesStatesManager->writeState(
								$property,
								Utils\ArrayHash::from($data),
								true,
								$source,
							);
						}
					}

					$result
						?->then(function () use ($entity, $property, $source, $routingKey, $data): void {
							$this->logger->debug(
								'Requested write value to channel property',
								[
									'source' => MetadataTypes\ModuleSource::DEVICES,
									'type' => 'state-consumer',
									'channel' => [
										'id' => $entity->getChannel()->toString(),
									],
									'property' => [
										'id' => $property->getId()->toString(),
										'identifier' => $property->getIdentifier(),
									],
									'data' => $data,
									'message' => [
										'routing_key' => $routingKey->getValue(),
										'source' => $source->getValue(),
										'data' => $entity->toArray(),
									],
								],
							);
						});
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

					$state = await($this->channelPropertiesStatesManager->readState($property));

					if ($state === null) {
						return;
					}

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
						MetadataTypes\RoutingKey::get(
							MetadataTypes\RoutingKey::CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED,
						),
						$state,
					)
						->then(function () use ($entity, $property, $source, $routingKey): void {
							$this->logger->debug(
								'Requested write value to channel property',
								[
									'source' => MetadataTypes\ModuleSource::DEVICES,
									'type' => 'state-consumer',
									'channel' => [
										'id' => $entity->getChannel()->toString(),
									],
									'property' => [
										'id' => $property->getId()->toString(),
										'identifier' => $property->getIdentifier(),
									],
									'message' => [
										'routing_key' => $routingKey->getValue(),
										'source' => $source->getValue(),
										'data' => $entity->toArray(),
									],
								],
							);
						})
						->catch(function (Throwable $ex): void {
							$this->logger->error(
								'Requested action could not be published for write action',
								[
									'source' => MetadataTypes\ModuleSource::DEVICES,
									'type' => 'channel-properties-states',
									'exception' => ApplicationHelpers\Logger::buildException($ex),
								],
							);
						});
				}
			}
		}
	}

}
