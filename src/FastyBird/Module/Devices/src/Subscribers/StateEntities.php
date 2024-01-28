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

use DateTimeInterface;
use Exception;
use FastyBird\Library\Application\Events as ApplicationEvents;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\States;
use IPub\Phone\Exceptions as PhoneExceptions;
use Nette;
use Nette\Utils;
use React\Promise;
use Symfony\Component\EventDispatcher;

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
		private readonly MetadataDocuments\DocumentFactory $documentFactory,
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
			Events\DevicePropertyStateEntityCreated::class => 'stateCreated',
			Events\DevicePropertyStateEntityUpdated::class => 'stateUpdated',
			Events\ChannelPropertyStateEntityCreated::class => 'stateCreated',
			Events\ChannelPropertyStateEntityUpdated::class => 'stateUpdated',

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
		$this->publishEntity(
			$this->useAsync,
			$event->getSource(),
			$event->getProperty(),
			$event->getRead(),
			$event->getGet(),
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
	 * @throws Utils\JsonException
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 */
	public function stateUpdated(
		Events\ConnectorPropertyStateEntityUpdated|Events\DevicePropertyStateEntityUpdated|Events\ChannelPropertyStateEntityUpdated $event,
	): void
	{
		$this->publishEntity(
			$this->useAsync,
			$event->getSource(),
			$event->getProperty(),
			$event->getRead(),
			$event->getGet(),
		);
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
		MetadataTypes\AutomatorSource|MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source,
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty $property,
		States\ConnectorProperty|States\ChannelProperty|States\DeviceProperty $readState,
		States\ConnectorProperty|States\ChannelProperty|States\DeviceProperty|null $getState = null,
	): Promise\PromiseInterface|bool
	{
		if ($property instanceof MetadataDocuments\DevicesModule\ConnectorDynamicProperty) {
			$routingKey = MetadataTypes\RoutingKey::get(
				MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_STATE_DOCUMENT_REPORTED,
			);

			$document = $this->documentFactory->create(
				MetadataDocuments\DevicesModule\ConnectorPropertyState::class,
				[
					'id' => $property->getId()->toString(),
					'connector' => $property->getConnector()->toString(),
					'read' => $readState->toArray(),
					'get' => $getState?->toArray(),
					'valid' => $readState->isValid(),
					'pending' => $readState->getPending() instanceof DateTimeInterface
						? $readState->getPending()->format(DateTimeInterface::ATOM)
						: $readState->getPending(),
					'created_at' => $readState->getCreatedAt()?->format(DateTimeInterface::ATOM),
					'updated_at' => $readState->getUpdatedAt()?->format(DateTimeInterface::ATOM),
				],
			);

		} elseif (
			$property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty
			|| $property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty
		) {
			$routingKey = MetadataTypes\RoutingKey::get(
				MetadataTypes\RoutingKey::DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED,
			);

			$document = $this->documentFactory->create(
				MetadataDocuments\DevicesModule\DevicePropertyState::class,
				[
					'id' => $property->getId()->toString(),
					'device' => $property->getDevice()->toString(),
					'read' => $readState->toArray(),
					'get' => $getState?->toArray(),
					'valid' => $readState->isValid(),
					'pending' => $readState->getPending() instanceof DateTimeInterface
						? $readState->getPending()->format(DateTimeInterface::ATOM)
						: $readState->getPending(),
					'created_at' => $readState->getCreatedAt()?->format(DateTimeInterface::ATOM),
					'updated_at' => $readState->getUpdatedAt()?->format(DateTimeInterface::ATOM),
				],
			);

		} else {
			$routingKey = MetadataTypes\RoutingKey::get(
				MetadataTypes\RoutingKey::CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED,
			);

			$document = $this->documentFactory->create(
				MetadataDocuments\DevicesModule\ChannelPropertyState::class,
				[
					'id' => $property->getId()->toString(),
					'channel' => $property->getChannel()->toString(),
					'read' => $readState->toArray(),
					'get' => $getState?->toArray(),
					'valid' => $readState->isValid(),
					'pending' => $readState->getPending() instanceof DateTimeInterface
						? $readState->getPending()->format(DateTimeInterface::ATOM)
						: $readState->getPending(),
					'created_at' => $readState->getCreatedAt()?->format(DateTimeInterface::ATOM),
					'updated_at' => $readState->getUpdatedAt()?->format(DateTimeInterface::ATOM),
				],
			);
		}

		return $this->getPublisher($async)->publish(
			$source,
			$routingKey,
			$document,
		);
	}

	private function getPublisher(bool $async): ExchangePublisher\Publisher|ExchangePublisher\Async\Publisher
	{
		return $async ? $this->asyncPublisher : $this->publisher;
	}

}
