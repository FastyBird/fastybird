<?php declare(strict_types = 1);

/**
 * RedisClient.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Bridge\RedisDbDevicesModule\Subscribers;

use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use FastyBird\Plugin\RedisDb\Events as RedisDbEvents;
use FastyBird\Plugin\RedisDb\Handlers as RedisDbHandlers;
use Nette\Utils;
use Psr\Log;
use Symfony\Component\EventDispatcher;
use function in_array;

/**
 * Redis DB client subscriber
 *
 * @package         FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisClient implements EventDispatcher\EventSubscriberInterface
{

	private const PROPERTIES_ACTIONS_ROUTING_KEYS = [
		MetadataTypes\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ACTION,
		MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ACTION,
		MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ACTION,
	];

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly RedisDbHandlers\Message $messageHandler,
		private readonly ExchangePublisher\Container $publisher,
		private readonly DevicesModels\DataStorage\ConnectorPropertiesRepository $connectorPropertiesRepository,
		private readonly DevicesModels\DataStorage\DevicePropertiesRepository $devicePropertiesRepository,
		private readonly DevicesModels\DataStorage\ChannelPropertiesRepository $channelPropertiesRepository,
		private readonly DevicesModels\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly DevicesModels\States\ConnectorPropertiesRepository $connectorPropertiesStatesRepository,
		private readonly DevicesModels\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly DevicesModels\States\DevicePropertiesRepository $devicePropertiesStatesRepository,
		private readonly DevicesModels\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly DevicesModels\States\ChannelPropertiesRepository $channelPropertiesStatesRepository,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public static function getSubscribedEvents(): array
	{
		return [
			RedisDbEvents\Startup::class => 'startup',
		];
	}

	public function startup(): void
	{
		$this->messageHandler->on(
			'message',
			function (
				MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\TriggerSource $source,
				MetadataTypes\RoutingKey $routingKey,
				MetadataEntities\Entity|null $entity,
			): void {
				$this->handle($source, $routingKey, $entity);
			},
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\NotImplemented
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function handle(
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\TriggerSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataEntities\Entity|null $entity,
	): void
	{
		if ($entity !== null) {
			if (in_array($routingKey->getValue(), self::PROPERTIES_ACTIONS_ROUTING_KEYS, true)) {
				if ($entity instanceof MetadataEntities\Actions\ActionConnectorProperty) {
					if ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::ACTION_SET)) {
						$property = $this->connectorPropertiesRepository->findById($entity->getProperty());

						if (!$property instanceof MetadataEntities\DevicesModule\ConnectorDynamicProperty) {
							return;
						}

						$valueToWrite = $this->normalizeValue($property, $entity->getExpectedValue());

						$state = $this->connectorPropertiesStatesRepository->findOne($property);

						if ($state !== null) {
							$this->connectorPropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
								'expectedValue' => $valueToWrite,
								'pending' => true,
							]));

						} else {
							$this->connectorPropertiesStatesManager->create($property, Utils\ArrayHash::from([
								'actualValue' => null,
								'expectedValue' => $valueToWrite,
								'pending' => true,
								'valid' => false,
							]));
						}
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

						$valueToWrite = $this->normalizeValue($property, $entity->getExpectedValue());

						$state = $this->devicePropertiesStatesRepository->findOne($property);

						if ($state !== null) {
							$this->devicePropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
								'expectedValue' => $valueToWrite,
								'pending' => true,
							]));

						} else {
							$this->devicePropertiesStatesManager->create($property, Utils\ArrayHash::from([
								'actualValue' => null,
								'expectedValue' => $valueToWrite,
								'pending' => true,
								'valid' => false,
							]));
						}
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

						$valueToWrite = $this->normalizeValue($property, $entity->getExpectedValue());

						$state = $this->channelPropertiesStatesRepository->findOne($property);

						if ($state !== null) {
							$this->channelPropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
								'expectedValue' => $valueToWrite,
								'pending' => true,
							]));

						} else {
							$this->channelPropertiesStatesManager->create($property, Utils\ArrayHash::from([
								'actualValue' => null,
								'expectedValue' => $valueToWrite,
								'pending' => true,
								'valid' => false,
							]));
						}
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
		} else {
			$this->logger->warning('Received data message without data', [
				'source' => MetadataTypes\BridgeSource::SOURCE_BRIDGE_REDISDB_DEVICES_STATES,
				'type' => 'subscriber',
			]);
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
		$valueToWrite = DevicesUtilities\ValueHelper::normalizeValue(
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

		return DevicesUtilities\ValueHelper::flattenValue($valueToWrite);
	}

}
