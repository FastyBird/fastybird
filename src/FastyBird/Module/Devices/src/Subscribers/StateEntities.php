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
use FastyBird\Library\Application\Events as ApplicationEvents;
use FastyBird\Library\Exchange\Documents as ExchangeDocuments;
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
use React\Promise;
use Symfony\Component\EventDispatcher;
use function array_merge;
use function assert;
use function React\Async\async;
use function React\Async\await;

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

	private bool $useAsync = false;

	public function __construct(
		private readonly Models\Configuration\Connectors\Properties\Repository $connectorPropertiesConfigurationRepository,
		private readonly Models\Configuration\Devices\Properties\Repository $devicePropertiesConfigurationRepository,
		private readonly Models\Configuration\Channels\Properties\Repository $channelPropertiesConfigurationRepository,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly Models\States\Async\ConnectorPropertiesManager $asyncConnectorPropertiesStatesManager,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Models\States\Async\DevicePropertiesManager $asyncDevicePropertiesStatesManager,
		private readonly Models\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Models\States\Async\ChannelPropertiesManager $asyncChannelPropertiesStatesManager,
		private readonly ExchangeDocuments\DocumentFactory $documentFactory,
		private readonly ExchangePublisher\Publisher $publisher,
		private readonly ExchangePublisher\Async\Publisher $asyncPublisher,
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

			ApplicationEvents\EventLoopStarted::class => 'enableAsync',
			ApplicationEvents\EventLoopStopped::class => 'disableAsync',
			ApplicationEvents\EventLoopStopping::class => 'disableAsync',
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
		if ($event instanceof Events\ConnectorPropertyStateEntityDeleted) {
			$findPropertyQuery = new Queries\Configuration\FindConnectorDynamicProperties();
			$findPropertyQuery->byId($event->getProperty());

			$property = $this->connectorPropertiesConfigurationRepository->findOneBy(
				$findPropertyQuery,
				MetadataDocuments\DevicesModule\ConnectorDynamicProperty::class,
			);
		} elseif ($event instanceof Events\DevicePropertyStateEntityDeleted) {
			$findPropertyQuery = new Queries\Configuration\FindDeviceDynamicProperties();
			$findPropertyQuery->byId($event->getProperty());

			$property = $this->devicePropertiesConfigurationRepository->findOneBy(
				$findPropertyQuery,
				MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
			);
		} else {
			$findPropertyQuery = new Queries\Configuration\FindChannelDynamicProperties();
			$findPropertyQuery->byId($event->getProperty());

			$property = $this->channelPropertiesConfigurationRepository->findOneBy(
				$findPropertyQuery,
				MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
			);
		}

		if ($property === null) {
			return;
		}

		$this->publishEntity($this->useAsync, $property);

		foreach ($this->findChildren($property) as $child) {
			$this->publishEntity($this->useAsync, $child);
		}
	}

	public function enableAsync(): void
	{
		$this->useAsync = true;
	}

	public function disableAsync(): void
	{
		$this->useAsync = false;
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
			if ($this->useAsync) {
				$this->asyncConnectorPropertiesStatesManager->read($property)
					->then(async(function (States\ConnectorProperty|null $state) use ($property): void {
						await($this->publishEntity(true, $property, $state));
					}));
			} else {
				$state = $this->connectorPropertiesStatesManager->read($property);

				$this->publishEntity(false, $property, $state);
			}
		} elseif ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
			if ($this->useAsync) {
				$this->asyncDevicePropertiesStatesManager->read($property)
					->then(async(function (States\DeviceProperty|null $state) use ($property): void {
						await($this->publishEntity(true, $property, $state));

						foreach ($this->findChildren($property) as $child) {
							assert($child instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty);

							$this->asyncDevicePropertiesStatesManager->read($child)
								->then(
									function (
										States\DeviceProperty|States\ChannelProperty|null $state,
									) use ($child): void {
										$this->publishEntity(true, $child, $state);
									},
								);
						}
					}));
			} else {
				$state = $this->devicePropertiesStatesManager->read($property);

				$this->publishEntity(false, $property, $state);
			}
		} else {
			if ($this->useAsync) {
				$this->asyncChannelPropertiesStatesManager->read($property)
					->then(async(function (States\ChannelProperty|null $state) use ($property): void {
						await($this->publishEntity(true, $property, $state));

						foreach ($this->findChildren($property) as $child) {
							assert($child instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty);

							$this->asyncChannelPropertiesStatesManager->read($child)
								->then(
									function (
										States\DeviceProperty|States\ChannelProperty|null $state,
									) use ($child): void {
										$this->publishEntity(true, $child, $state);
									},
								);
						}
					}));
			} else {
				$state = $this->channelPropertiesStatesManager->read($property);

				$this->publishEntity(false, $property, $state);
			}
		}

		if (!$this->useAsync) {
			foreach ($this->findChildren($property) as $child) {
				$state = $child instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty
					? $this->devicePropertiesStatesManager->read($child)
					: $this->channelPropertiesStatesManager->read($child);

				$this->publishEntity($this->useAsync, $child, $state);
			}
		}
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<bool> : bool)
	 *
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
		bool $async,
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty $property,
		States\ConnectorProperty|States\ChannelProperty|States\DeviceProperty|null $state = null,
	): Promise\PromiseInterface|bool
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

		return $this->getPublisher($async)->publish(
			MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
			$routingKey,
			$this->documentFactory->create(
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

	private function getPublisher(bool $async): ExchangePublisher\Publisher|ExchangePublisher\Async\Publisher
	{
		return $async ? $this->asyncPublisher : $this->publisher;
	}

}
