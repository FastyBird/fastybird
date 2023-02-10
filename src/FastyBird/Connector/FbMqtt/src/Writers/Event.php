<?php declare(strict_types = 1);

/**
 * Event.php
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
use FastyBird\Connector\FbMqtt\Clients;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use Nette\Utils;
use Psr\Log;
use Ramsey\Uuid;
use Symfony\Component\EventDispatcher;
use Throwable;
use function assert;

/**
 * Event based properties writer
 *
 * @package        FastyBird:FbMqttConnector!
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

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Helpers\Property $propertyStateHelper,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public static function getSubscribedEvents(): array
	{
		return [
			DevicesEvents\StateEntityCreated::class => 'stateChanged',
			DevicesEvents\StateEntityUpdated::class => 'stateChanged',
		];
	}

	public function connect(
		Entities\FbMqttConnector $connector,
		Clients\Client $client,
	): void
	{
		$this->clients[$connector->getPlainId()] = $client;
	}

	public function disconnect(
		Entities\FbMqttConnector $connector,
		Clients\Client $client,
	): void
	{
		unset($this->clients[$connector->getPlainId()]);
	}

	public function stateChanged(DevicesEvents\StateEntityCreated|DevicesEvents\StateEntityUpdated $event): void
	{
		foreach ($this->clients as $id => $client) {
			$this->processClient(Uuid\Uuid::fromString($id), $event, $client);
		}
	}

	public function processClient(
		Uuid\UuidInterface $connectorId,
		DevicesEvents\StateEntityCreated|DevicesEvents\StateEntityUpdated $event,
		Clients\Client $client,
	): void
	{
		$property = $event->getProperty();

		if ($property instanceof DevicesEntities\Devices\Properties\Dynamic) {
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
							'type' => 'event-writer',
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
		} elseif ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
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
							'type' => 'event-writer',
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
