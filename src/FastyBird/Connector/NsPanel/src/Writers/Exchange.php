<?php declare(strict_types = 1);

/**
 * Exchange.php
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
use Exception;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Clients;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use Throwable;
use function assert;

/**
 * Exchange based properties writer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Exchange implements Writer, ExchangeConsumers\Consumer
{

	use Nette\SmartObject;

	public const NAME = 'exchange';

	/** @var array<string, Clients\Client> */
	private array $clients = [];

	public function __construct(
		private readonly Helpers\Property $propertyStateHelper,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		private readonly ExchangeConsumers\Container $consumer,
		private readonly NsPanel\Logger $logger,
	)
	{
	}

	public function connect(
		Entities\NsPanelConnector $connector,
		Clients\Client $client,
	): void
	{
		$this->clients[$connector->getPlainId()] = $client;

		$this->consumer->enable(self::class);
	}

	public function disconnect(
		Entities\NsPanelConnector $connector,
		Clients\Client $client,
	): void
	{
		unset($this->clients[$connector->getPlainId()]);

		if ($this->clients === []) {
			$this->consumer->disable(self::class);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 */
	public function consume(
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\AutomatorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataEntities\Entity|null $entity,
	): void
	{
		foreach ($this->clients as $id => $client) {
			if ($client instanceof Clients\Gateway) {
				$this->processGatewayClient(Uuid\Uuid::fromString($id), $source, $entity, $client);
			} elseif ($client instanceof Clients\Device) {
				$this->processDeviceClient(Uuid\Uuid::fromString($id), $source, $entity, $client);
			}
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 */
	public function processGatewayClient(
		Uuid\UuidInterface $connectorId,
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\AutomatorSource $source,
		MetadataEntities\Entity|null $entity,
		Clients\Gateway $client,
	): void
	{
		if ($entity instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty) {
			if ($entity->getExpectedValue() === null || $entity->getPending() !== true) {
				return;
			}

			$findPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findPropertyQuery->byId($entity->getId());

			$property = $this->propertiesRepository->findOneBy($findPropertyQuery);

			if ($property === null) {
				return;
			}

			assert($property instanceof DevicesEntities\Channels\Properties\Dynamic);

			if (!$property->getChannel()->getDevice()->getConnector()->getId()->equals($connectorId)) {
				return;
			}

			$device = $property->getChannel()->getDevice();
			$channel = $property->getChannel();

			assert($device instanceof Entities\NsPanelDevice);
			assert($channel instanceof Entities\NsPanelChannel);

			$this->writeChannelProperty($client, $connectorId, $device, $channel, $property);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 */
	public function processDeviceClient(
		Uuid\UuidInterface $connectorId,
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\AutomatorSource $source,
		MetadataEntities\Entity|null $entity,
		Clients\Device $client,
	): void
	{
		if ($entity instanceof MetadataEntities\DevicesModule\ChannelMappedProperty) {
			$findPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findPropertyQuery->byId($entity->getId());

			$property = $this->propertiesRepository->findOneBy($findPropertyQuery);

			if ($property === null) {
				return;
			}

			assert($property instanceof DevicesEntities\Channels\Properties\Mapped);

			if (!$property->getChannel()->getDevice()->getConnector()->getId()->equals($connectorId)) {
				return;
			}

			$device = $property->getChannel()->getDevice();
			$channel = $property->getChannel();

			assert($device instanceof Entities\NsPanelDevice);
			assert($channel instanceof Entities\NsPanelChannel);

			$this->writeChannelProperty($client, $connectorId, $device, $channel, $property);
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
		if ($client instanceof Clients\Gateway && !$property instanceof DevicesEntities\Channels\Properties\Dynamic) {
			return;
		}

		if ($client instanceof Clients\Device && !$property instanceof DevicesEntities\Channels\Properties\Mapped) {
			return;
		}

		$client->writeChannelProperty($device, $channel, $property)
			->then(function () use ($property): void {
				if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
					$state = $this->channelPropertiesStates->getValue($property);

					if ($state !== null && $state->getExpectedValue() === null) {
						return;
					}

					$this->propertyStateHelper->setValue(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::PENDING_KEY => $this->dateTimeFactory->getNow()->format(
								DateTimeInterface::ATOM,
							),
						]),
					);
				}
			})
			->otherwise(function (Throwable $ex) use ($connectorId, $device, $channel, $property): void {
				$this->logger->error(
					'Could not write property state',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
						'type' => 'exchange-writer',
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

}