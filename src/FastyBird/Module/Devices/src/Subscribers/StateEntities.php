<?php declare(strict_types = 1);

/**
 * StateEntities.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Module\Devices\Subscribers;

use Exception;
use FastyBird\Library\Exchange\Entities as ExchangeEntities;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
use IPub\Phone\Exceptions as PhoneExceptions;
use Nette;
use Nette\Utils;
use Symfony\Component\EventDispatcher;
use function array_merge;

/**
 * Devices state entities events
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StateEntities implements EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		private readonly Models\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly ExchangeEntities\EntityFactory $entityFactory,
		private readonly ExchangePublisher\Publisher $publisher,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\StateEntityCreated::class => 'stateCreated',
			Events\StateEntityUpdated::class => 'stateUpdated',
			Events\StateEntityDeleted::class => 'stateDeleted',
		];
	}

	/**
	 * @throws Exception
	 * @throws Exceptions\InvalidState
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
	public function stateCreated(Events\StateEntityCreated $event): void
	{
		$this->publishEntity($event->getProperty(), $event->getState());

		$parent = $this->findParent($event->getProperty());

		if ($parent !== null) {
			$this->publishEntity($parent, $event->getState());
		}
	}

	/**
	 * @throws Exception
	 * @throws Exceptions\InvalidState
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
	public function stateUpdated(Events\StateEntityUpdated $event): void
	{
		$this->publishEntity($event->getProperty(), $event->getState());

		$parent = $this->findParent($event->getProperty());

		if ($parent !== null) {
			$this->publishEntity($parent, $event->getState());
		}
	}

	/**
	 * @throws Exception
	 * @throws Exceptions\InvalidState
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
	public function stateDeleted(Events\StateEntityDeleted $event): void
	{
		$this->publishEntity($event->getProperty());

		$parent = $this->findParent($event->getProperty());

		if ($parent !== null) {
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
		MetadataEntities\DevicesModule\DynamicProperty|MetadataEntities\DevicesModule\VariableProperty|MetadataEntities\DevicesModule\MappedProperty|Entities\Property $property,
		States\ConnectorProperty|States\ChannelProperty|States\DeviceProperty|null $state = null,
	): void
	{
		if (
			$property instanceof MetadataEntities\DevicesModule\ConnectorDynamicProperty
			|| $property instanceof Entities\Connectors\Properties\Property
		) {
			$routingKey = MetadataTypes\RoutingKey::get(
				MetadataTypes\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ENTITY_REPORTED,
			);

		} elseif (
			$property instanceof MetadataEntities\DevicesModule\DeviceDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\DeviceMappedProperty
			|| $property instanceof Entities\Devices\Properties\Property
		) {
			$routingKey = MetadataTypes\RoutingKey::get(
				MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_REPORTED,
			);

		} elseif (
			$property instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\ChannelMappedProperty
			|| $property instanceof Entities\Channels\Properties\Property
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
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function findParent(
		MetadataEntities\DevicesModule\DynamicProperty|MetadataEntities\DevicesModule\VariableProperty|MetadataEntities\DevicesModule\MappedProperty|Entities\Property $property,
	): Entities\Property|null
	{
		if (
			$property instanceof MetadataEntities\DevicesModule\ConnectorDynamicProperty
			|| $property instanceof Entities\Connectors\Properties\Dynamic
		) {
			return null;
		} elseif (
			$property instanceof MetadataEntities\DevicesModule\DeviceDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\DeviceMappedProperty
		) {
			if ($property->getParent() !== null) {
				$findPropertyQuery = new Queries\FindDeviceProperties();
				$findPropertyQuery->byId($property->getParent());

				return $this->devicePropertiesRepository->findOneBy($findPropertyQuery);
			}
		} elseif (
			$property instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\ChannelMappedProperty
		) {
			if ($property->getParent() !== null) {
				$findPropertyQuery = new Queries\FindChannelProperties();
				$findPropertyQuery->byId($property->getParent());

				return $this->channelPropertiesRepository->findOneBy($findPropertyQuery);
			}
		} elseif (
			$property instanceof Entities\Devices\Properties\Dynamic
			|| $property instanceof Entities\Devices\Properties\Mapped
		) {
			return $property->getParent();
		} elseif (
			$property instanceof Entities\Channels\Properties\Dynamic
			|| $property instanceof Entities\Channels\Properties\Mapped
		) {
			return $property->getParent();
		}

		return null;
	}

}
