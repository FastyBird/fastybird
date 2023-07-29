<?php declare(strict_types = 1);

/**
 * Event.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           12.07.23
 */

namespace FastyBird\Connector\NsPanel\Writers;

use DateTimeInterface;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Clients;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use Symfony\Component\EventDispatcher;
use Throwable;
use function assert;

/**
 * Event based properties writer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Event implements Writer, EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public const NAME = 'event';

	/** @var array<string, Clients\Client> */
	private array $clients = [];

	public function __construct(
		private readonly Helpers\Property $propertyStateHelper,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		private readonly NsPanel\Logger $logger,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			DevicesEvents\StateEntityCreated::class => 'stateChanged',
			DevicesEvents\StateEntityUpdated::class => 'stateChanged',
		];
	}

	public function connect(
		Entities\NsPanelConnector $connector,
		Clients\Client $client,
	): void
	{
		$this->clients[$connector->getPlainId()] = $client;
	}

	public function disconnect(
		Entities\NsPanelConnector $connector,
		Clients\Client $client,
	): void
	{
		unset($this->clients[$connector->getPlainId()]);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function stateChanged(DevicesEvents\StateEntityCreated|DevicesEvents\StateEntityUpdated $event): void
	{
		foreach ($this->clients as $id => $client) {
			if ($client instanceof Clients\Gateway) {
				$this->processGatewayClient(Uuid\Uuid::fromString($id), $event, $client);
			} elseif ($client instanceof Clients\Device) {
				$this->processDeviceClient(Uuid\Uuid::fromString($id), $event, $client);
			}
		}
	}

	public function processGatewayClient(
		Uuid\UuidInterface $connectorId,
		DevicesEvents\StateEntityCreated|DevicesEvents\StateEntityUpdated $event,
		Clients\Client $client,
	): void
	{
		$property = $event->getProperty();

		if (!$property instanceof DevicesEntities\Channels\Properties\Dynamic) {
			return;
		}

		$state = $event->getState();

		if ($state->getExpectedValue() === null || $state->getPending() !== true) {
			return;
		}

		if (!$property->getChannel()->getDevice()->getConnector()->getId()->equals($connectorId)) {
			return;
		}

		$device = $property->getChannel()->getDevice();
		$channel = $property->getChannel();

		assert($device instanceof Entities\Devices\SubDevice);
		assert($channel instanceof Entities\NsPanelChannel);

		$this->writeChannelProperty($client, $connectorId, $device, $channel, $property);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function processDeviceClient(
		Uuid\UuidInterface $connectorId,
		DevicesEvents\StateEntityCreated|DevicesEvents\StateEntityUpdated $event,
		Clients\Client $client,
	): void
	{
		$property = $event->getProperty();

		foreach ($this->findChildren($property) as $child) {
			if (
				!$child->getChannel()->getDevice()->getConnector()->getId()->equals($connectorId)
			) {
				continue;
			}

			$state = $this->channelPropertiesStates->readValue($child);

			if ($state === null) {
				continue;
			}

			$device = $child->getChannel()->getDevice();
			$channel = $child->getChannel();

			assert($device instanceof Entities\Devices\ThirdPartyDevice);
			assert($channel instanceof Entities\NsPanelChannel);

			$this->writeChannelProperty($client, $connectorId, $device, $channel, $child);
		}
	}

	private function writeChannelProperty(
		Clients\Client $client,
		Uuid\UuidInterface $connectorId,
		Entities\NsPanelDevice $device,
		Entities\NsPanelChannel $channel,
		DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped $property,
	): void
	{
		$now = $this->dateTimeFactory->getNow();

		$client->writeChannelProperty($device, $channel, $property)
			->then(function () use ($property, $now): void {
				if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
					$state = $this->channelPropertiesStates->getValue($property);

					if ($state?->getExpectedValue() !== null) {
						$this->propertyStateHelper->setValue(
							$property,
							Utils\ArrayHash::from([
								DevicesStates\Property::PENDING_KEY => $now->format(DateTimeInterface::ATOM),
							]),
						);
					}
				}
			})
			->otherwise(function (Throwable $ex) use ($connectorId, $device, $channel, $property): void {
				$this->logger->error(
					'Could not write property state',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
						'type' => 'event-writer',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $connectorId->toString(),
						],
						'device' => [
							'id' => $device->getPlainId(),
						],
						'channel' => [
							'id' => $channel->getPlainId(),
						],
						'property' => [
							'id' => $property->getPlainId(),
						],
					],
				);

				if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
					$this->propertyStateHelper->setValue(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::EXPECTED_VALUE_KEY => null,
							DevicesStates\Property::PENDING_KEY => false,
						]),
					);
				}
			});
	}

	/**
	 * @return array<DevicesEntities\Channels\Properties\Mapped>
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	private function findChildren(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataEntities\DevicesModule\DynamicProperty|DevicesEntities\Connectors\Properties\Dynamic|DevicesEntities\Devices\Properties\Dynamic|DevicesEntities\Channels\Properties\Dynamic $property,
	): array
	{
		if ($property instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty) {
			$findPropertyQuery = new DevicesQueries\FindChannelMappedProperties();
			$findPropertyQuery->byParentId($property->getId());

			return $this->channelPropertiesRepository->findAllBy(
				$findPropertyQuery,
				DevicesEntities\Channels\Properties\Mapped::class,
			);
		}

		return [];
	}

}
