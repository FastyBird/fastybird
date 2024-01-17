<?php declare(strict_types = 1);

/**
 * StateEntities.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Module\Devices\Subscribers;

use Exception;
use FastyBird\Library\Exchange\Documents as ExchangeEntities;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
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
		private readonly Models\Configuration\Devices\Properties\Repository $devicePropertiesConfigurationRepository,
		private readonly Models\Configuration\Channels\Properties\Repository $channelPropertiesConfigurationRepository,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Models\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly ExchangeEntities\DocumentFactory $entityFactory,
		private readonly ExchangePublisher\Publisher $publisher,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\ConnectorPropertyStateEntityCreated::class => 'stateCreated',
			Events\ConnectorPropertyStateEntityUpdated::class => 'stateUpdated',
			Events\ConnectorPropertyStateEntityDeleted::class => 'stateDeleted',
			Events\DevicePropertyStateEntityCreated::class => 'stateCreated',
			Events\DevicePropertyStateEntityUpdated::class => 'stateUpdated',
			Events\DevicePropertyStateEntityDeleted::class => 'stateDeleted',
			Events\ChannelPropertyStateEntityCreated::class => 'stateCreated',
			Events\ChannelPropertyStateEntityUpdated::class => 'stateUpdated',
			Events\ChannelPropertyStateEntityDeleted::class => 'stateDeleted',
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
	public function stateCreated(
		Events\ConnectorPropertyStateEntityCreated|Events\DevicePropertyStateEntityCreated|Events\ChannelPropertyStateEntityCreated $event,
	): void
	{
		$this->processEntity($event->getProperty());
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
	public function stateUpdated(
		Events\ConnectorPropertyStateEntityUpdated|Events\DevicePropertyStateEntityUpdated|Events\ChannelPropertyStateEntityUpdated $event,
	): void
	{
		$this->processEntity($event->getProperty());
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
	public function stateDeleted(
		Events\ConnectorPropertyStateEntityDeleted|Events\DevicePropertyStateEntityDeleted|Events\ChannelPropertyStateEntityDeleted $event,
	): void
	{
		$this->publishEntity($event->getProperty());

		foreach ($this->findChildren($event->getProperty()) as $child) {
			$this->publishEntity($child);
		}
	}

	/**
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Utils\JsonException
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 */
	private function processEntity(
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\ChannelDynamicProperty $property,
	): void
	{
		if (
			$property instanceof MetadataDocuments\DevicesModule\ConnectorDynamicProperty
		) {
			$state = $this->connectorPropertiesStatesManager->read($property);

		} elseif ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
			$state = $this->devicePropertiesStatesManager->read($property);

		} else {
			$state = $this->channelPropertiesStatesManager->read($property);
		}

		$this->publishEntity($property, $state);

		foreach ($this->findChildren($property) as $child) {
			$state = $child instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty
				? $this->devicePropertiesStatesManager->read($child)
				: $this->channelPropertiesStatesManager->read($child);

			$this->publishEntity($child, $state);
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
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty $property,
		States\ConnectorProperty|States\ChannelProperty|States\DeviceProperty|null $state = null,
	): void
	{
		if ($property instanceof MetadataDocuments\DevicesModule\ConnectorDynamicProperty) {
			$routingKey = MetadataTypes\RoutingKey::get(
				MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_DOCUMENT_REPORTED,
			);

		} elseif (
			$property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty
			|| $property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty
		) {
			$routingKey = MetadataTypes\RoutingKey::get(
				MetadataTypes\RoutingKey::DEVICE_PROPERTY_DOCUMENT_REPORTED,
			);

		} else {
			$routingKey = MetadataTypes\RoutingKey::get(
				MetadataTypes\RoutingKey::CHANNEL_PROPERTY_DOCUMENT_REPORTED,
			);
		}

		$this->publisher->publish(
			MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
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
	 * @return array<MetadataDocuments\DevicesModule\DeviceMappedProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty>
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function findChildren(
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\ChannelDynamicProperty $property,
	): array
	{
		if ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
			$findDevicePropertiesQuery = new Queries\Configuration\FindDeviceMappedProperties();
			$findDevicePropertiesQuery->forParent($property);

			return $this->devicePropertiesConfigurationRepository->findAllBy(
				$findDevicePropertiesQuery,
				MetadataDocuments\DevicesModule\DeviceMappedProperty::class,
			);
		} elseif ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
			$findDevicePropertiesQuery = new Queries\Configuration\FindChannelMappedProperties();
			$findDevicePropertiesQuery->forParent($property);

			return $this->channelPropertiesConfigurationRepository->findAllBy(
				$findDevicePropertiesQuery,
				MetadataDocuments\DevicesModule\ChannelMappedProperty::class,
			);
		}

		return [];
	}

}
