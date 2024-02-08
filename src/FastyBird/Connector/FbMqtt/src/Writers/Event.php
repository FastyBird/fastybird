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

use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Queue\Messages\WriteChannelPropertyState;
use FastyBird\Connector\FbMqtt\Queue\Messages\WriteDevicePropertyState;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Symfony\Component\EventDispatcher;

/**
 * Event based properties writer
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Event extends Periodic implements Writer, EventDispatcher\EventSubscriberInterface
{

	public const NAME = 'event';

	public static function getSubscribedEvents(): array
	{
		return [
			DevicesEvents\DevicePropertyStateEntityCreated::class => 'stateChanged',
			DevicesEvents\DevicePropertyStateEntityUpdated::class => 'stateChanged',
			DevicesEvents\ChannelPropertyStateEntityCreated::class => 'stateChanged',
			DevicesEvents\ChannelPropertyStateEntityUpdated::class => 'stateChanged',
		];
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
		if (
			$event->getGet()->getExpectedValue() === null
			|| $event->getGet()->getPending() !== true
		) {
			return;
		}

		if (
			$event instanceof DevicesEvents\DevicePropertyStateEntityCreated
			|| $event instanceof DevicesEvents\DevicePropertyStateEntityUpdated
		) {
			$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
			$findDeviceQuery->forConnector($this->connector);
			$findDeviceQuery->byId($event->getProperty()->getDevice());
			$findDeviceQuery->byType(Entities\FbMqttDevice::TYPE);

			$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

			if ($device === null) {
				return;
			}

			$this->queue->append(
				$this->messageBuilder->create(
					WriteDevicePropertyState::class,
					[
						'connector' => $this->connector->getId(),
						'device' => $device->getId(),
						'property' => $event->getProperty()->getId(),
						'state' => $event->getGet()->toArray(),
					],
				),
			);
		} else {
			$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
			$findChannelQuery->byId($event->getProperty()->getChannel());
			$findChannelQuery->byType(Entities\FbMqttChannel::TYPE);

			$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

			if ($channel === null) {
				return;
			}

			$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
			$findDeviceQuery->forConnector($this->connector);
			$findDeviceQuery->byId($channel->getDevice());
			$findDeviceQuery->byType(Entities\FbMqttDevice::TYPE);

			$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

			if ($device === null) {
				return;
			}

			$this->queue->append(
				$this->messageBuilder->create(
					WriteChannelPropertyState::class,
					[
						'connector' => $this->connector->getId(),
						'device' => $device->getId(),
						'channel' => $channel->getId(),
						'property' => $event->getProperty()->getId(),
						'state' => $event->getGet()->toArray(),
					],
				),
			);
		}
	}

}
