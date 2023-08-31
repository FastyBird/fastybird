<?php declare(strict_types = 1);

/**
 * Event.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           14.12.22
 */

namespace FastyBird\Connector\Shelly\Writers;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Queries;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use Symfony\Component\EventDispatcher;
use function assert;

/**
 * Event based properties writer
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Event implements Writer, EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public const NAME = 'event';

	public function __construct(
		private readonly Entities\ShellyConnector $connector,
		private readonly Helpers\Entity $entityHelper,
		private readonly Queue\Queue $queue,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			DevicesEvents\DevicePropertyStateEntityCreated::class => 'stateChanged',
			DevicesEvents\DevicePropertyStateEntityUpdated::class => 'stateChanged',
			DevicesEvents\ChannelPropertyStateEntityCreated::class => 'stateChanged',
			DevicesEvents\ChannelPropertyStateEntityUpdated::class => 'stateChanged',
		];
	}

	public function connect(): void
	{
		// Nothing to do here
	}

	public function disconnect(): void
	{
		// Nothing to do here
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	public function stateChanged(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		DevicesEvents\DevicePropertyStateEntityCreated|DevicesEvents\DevicePropertyStateEntityUpdated|DevicesEvents\ChannelPropertyStateEntityCreated|DevicesEvents\ChannelPropertyStateEntityUpdated $event,
	): void
	{
		$property = $event->getProperty();

		$state = $event->getState();

		if ($state->getExpectedValue() === null || $state->getPending() !== true) {
			return;
		}

		if (
			$property instanceof DevicesEntities\Devices\Properties\Dynamic
			|| $property instanceof MetadataEntities\DevicesModule\DeviceDynamicProperty
		) {
			if ($property->getDevice() instanceof DevicesEntities\Devices\Device) {
				$device = $property->getDevice();

			} else {
				$findDeviceQuery = new Queries\FindDevices();
				$findDeviceQuery->byId($property->getDevice());

				$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\ShellyDevice::class);
			}

			if ($device === null) {
				return;
			}

			if (!$device->getConnector()->getId()->equals($this->connector->getId())) {
				return;
			}

			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\WriteDevicePropertyState::class,
					[
						'connector' => $this->connector->getId()->toString(),
						'device' => $device->getId()->toString(),
						'property' => $property->getId()->toString(),
					],
				),
			);

		} elseif (
			$property instanceof DevicesEntities\Channels\Properties\Dynamic
			|| $property instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty
		) {
			if ($property->getChannel() instanceof DevicesEntities\Channels\Channel) {
				$channel = $property->getChannel();

			} else {
				$findChannelQuery = new DevicesQueries\FindChannels();
				$findChannelQuery->byId($property->getChannel());

				$channel = $this->channelsRepository->findOneBy($findChannelQuery);
			}

			if ($channel === null) {
				return;
			}

			$device = $channel->getDevice();
			assert($device instanceof Entities\ShellyDevice);

			if (!$device->getConnector()->getId()->equals($this->connector->getId())) {
				return;
			}

			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\WriteChannelPropertyState::class,
					[
						'connector' => $this->connector->getId()->toString(),
						'device' => $device->getId()->toString(),
						'channel' => $channel->getId()->toString(),
						'property' => $property->getId()->toString(),
					],
				),
			);
		}
	}

}
