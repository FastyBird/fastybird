<?php declare(strict_types = 1);

/**
 * Event.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           14.12.22
 */

namespace FastyBird\Connector\Tuya\Writers;

use DateTimeInterface;
use FastyBird\Connector\Tuya\Clients;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use Nette\Utils;
use Psr\Log;
use Symfony\Component\EventDispatcher;
use Throwable;
use function assert;

/**
 * Event based properties writer
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Event implements Writer, EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public const NAME = 'event';

	private Entities\TuyaConnector|null $connector = null;

	private Clients\Client|null $client = null;

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
		Entities\TuyaConnector $connector,
		Clients\Client $client,
	): void
	{
		$this->connector = $connector;
		$this->client = $client;
	}

	public function disconnect(): void
	{
		// Nothing to do here
	}

	public function stateChanged(DevicesEvents\StateEntityCreated|DevicesEvents\StateEntityUpdated $event): void
	{
		assert($this->connector instanceof Entities\TuyaConnector);

		$property = $event->getProperty();

		if (!$property instanceof DevicesEntities\Channels\Properties\Dynamic) {
			return;
		}

		if (!$property->getChannel()->getDevice()->getConnector()->getId()->equals($this->connector->getId())) {
			return;
		}

		$device = $property->getChannel()->getDevice();
		$channel = $property->getChannel();

		assert($device instanceof Entities\TuyaDevice);

		$this->client?->writeChannelProperty($device, $channel, $property)
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
			->otherwise(function (Throwable $ex) use ($device, $channel, $property): void {
				$this->logger->error(
					'Could write new property state',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'event-writer',
						'group' => 'writer',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'connector' => [
							'id' => $this->connector?->getPlainId(),
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
