<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           18.01.23
 */

namespace FastyBird\Connector\FbMqtt\Writers;

use DateTimeInterface;
use Exception;
use FastyBird\Connector\FbMqtt\Clients;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use Nette\Utils;
use Psr\Log;
use Ramsey\Uuid;
use Throwable;
use function assert;

/**
 * Exchange based properties writer
 *
 * @package        FastyBird:FbMqttConnector!
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

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Helpers\Property $propertyStateHelper,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly ExchangeConsumers\Container $consumer,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function connect(
		Entities\FbMqttConnector $connector,
		Clients\Client $client,
	): void
	{
		$this->clients[$connector->getPlainId()] = $client;

		$this->consumer->enable(self::class);
	}

	public function disconnect(
		Entities\FbMqttConnector $connector,
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
			$this->processClient(Uuid\Uuid::fromString($id), $source, $entity, $client);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 */
	public function processClient(
		Uuid\UuidInterface $connectorId,
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\AutomatorSource $source,
		MetadataEntities\Entity|null $entity,
		Clients\Client $client,
	): void
	{
		if ($entity instanceof MetadataEntities\DevicesModule\DeviceDynamicProperty) {
			if ($entity->getExpectedValue() === null || $entity->getPending() !== true) {
				return;
			}

			$findPropertyQuery = new DevicesQueries\FindDeviceProperties();
			$findPropertyQuery->byId($entity->getId());

			$property = $this->devicePropertiesRepository->findOneBy($findPropertyQuery);

			if ($property === null) {
				return;
			}

			assert($property instanceof DevicesEntities\Devices\Properties\Dynamic);

			if (!$property->getDevice()->getConnector()->getId()->equals($connectorId)) {
				return;
			}

			$device = $property->getDevice();

			assert($device instanceof Entities\FbMqttDevice);

			$client->writeProperty($device, $property)
				->then(function () use ($property): void {
					$this->propertyStateHelper->setValue(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::PENDING_KEY => $this->dateTimeFactory->getNow()->format(
								DateTimeInterface::ATOM,
							),
						]),
					);
				})
				->otherwise(function (Throwable $ex) use ($connectorId, $device, $property): void {
					$this->logger->error(
						'Could write new device property state',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
							'type' => 'exchange-writer',
							'group' => 'writer',
							'exception' => [
								'message' => $ex->getMessage(),
								'code' => $ex->getCode(),
							],
							'connector' => [
								'id' => $connectorId->toString(),
							],
							'device' => [
								'id' => $device->getPlainId(),
							],
							'property' => [
								'id' => $property->getPlainId(),
							],
						],
					);

					$this->propertyStateHelper->setValue(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::EXPECTED_VALUE_KEY => null,
							DevicesStates\Property::PENDING_KEY => false,
						]),
					);
				});
		} elseif ($entity instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty) {
			if ($entity->getExpectedValue() === null || $entity->getPending() !== true) {
				return;
			}

			$findPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findPropertyQuery->byId($entity->getId());

			$property = $this->channelPropertiesRepository->findOneBy($findPropertyQuery);

			if ($property === null) {
				return;
			}

			assert($property instanceof DevicesEntities\Channels\Properties\Dynamic);

			if (!$property->getChannel()->getDevice()->getConnector()->getId()->equals($connectorId)) {
				return;
			}

			$device = $property->getChannel()->getDevice();
			$channel = $property->getChannel();

			assert($device instanceof Entities\FbMqttDevice);

			$client->writeProperty($device, $property)
				->then(function () use ($property): void {
					$this->propertyStateHelper->setValue(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::PENDING_KEY => $this->dateTimeFactory->getNow()->format(
								DateTimeInterface::ATOM,
							),
						]),
					);
				})
				->otherwise(function (Throwable $ex) use ($connectorId, $device, $channel, $property): void {
					$this->logger->error(
						'Could write new channel property state',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
							'type' => 'exchange-writer',
							'group' => 'writer',
							'exception' => [
								'message' => $ex->getMessage(),
								'code' => $ex->getCode(),
							],
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

					$this->propertyStateHelper->setValue(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::EXPECTED_VALUE_KEY => null,
							DevicesStates\Property::PENDING_KEY => false,
						]),
					);
				});
		}
	}

}
