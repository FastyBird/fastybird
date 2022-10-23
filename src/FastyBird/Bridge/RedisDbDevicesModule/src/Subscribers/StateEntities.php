<?php declare(strict_types = 1);

/**
 * StateEntities.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Bridge\RedisDbDevicesModule\Subscribers;

use Exception;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Plugin\RedisDb\Publishers as RedisDbPublishers;
use IPub\Phone\Exceptions as PhoneExceptions;
use Nette;
use Nette\Utils;
use Symfony\Component\EventDispatcher;
use function array_merge;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StateEntities implements EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\DataStorage\ConnectorPropertiesRepository $connectorPropertiesRepository,
		private readonly DevicesModels\DataStorage\DevicePropertiesRepository $devicePropertiesRepository,
		private readonly DevicesModels\DataStorage\ChannelPropertiesRepository $channelPropertiesRepository,
		private readonly MetadataEntities\RoutingFactory $entityFactory,
		private readonly RedisDbPublishers\Publisher $publisher,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			DevicesEvents\StateEntityCreated::class => 'stateCreated',
			DevicesEvents\StateEntityUpdated::class => 'stateUpdated',
			DevicesEvents\StateEntityDeleted::class => 'stateDeleted',
		];
	}

	/**
	 * @throws Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Utils\JsonException
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 */
	public function stateCreated(DevicesEvents\StateEntityCreated $event): void
	{
		$this->refreshRepository($event->getProperty());

		$this->publishEntity($event->getProperty(), $event->getState());

		$parent = $this->findParent($event->getProperty());

		if ($parent !== null) {
			$this->refreshRepository($parent);

			$this->publishEntity($parent, $event->getState());
		}
	}

	/**
	 * @throws Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Utils\JsonException
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 */
	public function stateUpdated(DevicesEvents\StateEntityUpdated $event): void
	{
		if ($event->getPreviousState()->toArray() !== $event->getState()->toArray()) {
			$this->refreshRepository($event->getProperty());
		}

		$this->publishEntity($event->getProperty(), $event->getState());

		$parent = $this->findParent($event->getProperty());

		if ($parent !== null) {
			if ($event->getPreviousState()->toArray() !== $event->getState()->toArray()) {
				$this->refreshRepository($parent);
			}

			$this->publishEntity($parent, $event->getState());
		}
	}

	/**
	 * @throws Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Utils\JsonException
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 */
	public function stateDeleted(DevicesEvents\StateEntityDeleted $event): void
	{
		$this->refreshRepository($event->getProperty());

		$this->publishEntity($event->getProperty());

		$parent = $this->findParent($event->getProperty());

		if ($parent !== null) {
			$this->refreshRepository($parent);

			$this->publishEntity($parent);
		}
	}

	/**
	 * @throws Exception
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Utils\JsonException
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 */
	private function publishEntity(
		MetadataEntities\DevicesModule\DynamicProperty|MetadataEntities\DevicesModule\VariableProperty|MetadataEntities\DevicesModule\MappedProperty|DevicesEntities\Property $property,
		DevicesStates\ConnectorProperty|DevicesStates\ChannelProperty|DevicesStates\DeviceProperty|null $state = null,
	): void
	{
		if (
			$property instanceof MetadataEntities\DevicesModule\ConnectorDynamicProperty
			|| $property instanceof DevicesEntities\Connectors\Properties\Property
		) {
			$routingKey = MetadataTypes\RoutingKey::get(
				MetadataTypes\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ENTITY_REPORTED,
			);

		} elseif (
			$property instanceof MetadataEntities\DevicesModule\DeviceDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\DeviceMappedProperty
			|| $property instanceof DevicesEntities\Devices\Properties\Property
		) {
			$routingKey = MetadataTypes\RoutingKey::get(
				MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_REPORTED,
			);

		} elseif (
			$property instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\ChannelMappedProperty
			|| $property instanceof DevicesEntities\Channels\Properties\Property
		) {
			$routingKey = MetadataTypes\RoutingKey::get(
				MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_REPORTED,
			);

		} else {
			return;
		}

		$this->publisher->publish(
			MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
			$routingKey,
			$this->entityFactory->create(
				Utils\Json::encode(
					array_merge(
						$property->toArray(),
						$state?->toArray() ?? [],
					),
				),
				$routingKey,
			),
		);
	}

	/**
	 * @throws Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function findParent(
		MetadataEntities\DevicesModule\DynamicProperty|MetadataEntities\DevicesModule\VariableProperty|MetadataEntities\DevicesModule\MappedProperty|DevicesEntities\Property $property,
	): MetadataEntities\DevicesModule\DynamicProperty|MetadataEntities\DevicesModule\VariableProperty|MetadataEntities\DevicesModule\MappedProperty|DevicesEntities\Property|null
	{
		if (
			$property instanceof MetadataEntities\DevicesModule\ConnectorDynamicProperty
			|| $property instanceof DevicesEntities\Connectors\Properties\Dynamic
		) {
			return null;
		} elseif (
			$property instanceof MetadataEntities\DevicesModule\DeviceDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\DeviceMappedProperty
		) {
			if ($property->getParent() !== null) {
				return $this->devicePropertiesRepository->findById($property->getParent());
			}
		} elseif (
			$property instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\ChannelMappedProperty
		) {
			if ($property->getParent() !== null) {
				return $this->channelPropertiesRepository->findById($property->getParent());
			}
		} elseif (
			$property instanceof DevicesEntities\Devices\Properties\Dynamic
			|| $property instanceof DevicesEntities\Devices\Properties\Mapped
		) {
			return $property->getParent();
		} elseif (
			$property instanceof DevicesEntities\Channels\Properties\Dynamic
			|| $property instanceof DevicesEntities\Channels\Properties\Mapped
		) {
			return $property->getParent();
		}

		return null;
	}

	private function refreshRepository(
		MetadataEntities\DevicesModule\DynamicProperty|MetadataEntities\DevicesModule\VariableProperty|MetadataEntities\DevicesModule\MappedProperty|DevicesEntities\Property $property,
	): void
	{
		if (
			$property instanceof MetadataEntities\DevicesModule\ConnectorDynamicProperty
			|| $property instanceof DevicesEntities\Connectors\Properties\Dynamic
		) {
			$this->connectorPropertiesRepository->reset($property->getId());
		} elseif (
			$property instanceof MetadataEntities\DevicesModule\DeviceDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\DeviceMappedProperty
			|| $property instanceof DevicesEntities\Devices\Properties\Dynamic
			|| $property instanceof DevicesEntities\Devices\Properties\Mapped
		) {
			$this->devicePropertiesRepository->reset($property->getId());
		} elseif (
			$property instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\ChannelMappedProperty
			|| $property instanceof DevicesEntities\Channels\Properties\Dynamic
			|| $property instanceof DevicesEntities\Channels\Properties\Mapped
		) {
			$this->channelPropertiesRepository->reset($property->getId());
		}
	}

}
