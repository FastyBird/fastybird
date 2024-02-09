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

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Queue\Messages\StoreDeviceConnectionState;
use FastyBird\Connector\NsPanel\Queue\Messages\WriteSubDeviceState;
use FastyBird\Connector\NsPanel\Queue\Messages\WriteThirdPartyDeviceState;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Symfony\Component\EventDispatcher;

/**
 * Event based properties writer
 *
 * @package        FastyBird:NsPanelConnector!
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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function stateChanged(
		DevicesEvents\ChannelPropertyStateEntityCreated|DevicesEvents\ChannelPropertyStateEntityUpdated $event,
	): void
	{
		$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
		$findChannelQuery->byId($event->getProperty()->getChannel());
		$findChannelQuery->byType(Entities\Channels\Channel::TYPE);

		$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

		if ($channel === null) {
			return;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->forConnector($this->connector);
		$findDeviceQuery->byId($channel->getDevice());

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			return;
		}

		if ($device->getType() === Entities\Devices\SubDevice::TYPE) {
			$this->queue->append(
				$this->messageBuilder->create(
					WriteSubDeviceState::class,
					[
						'connector' => $device->getConnector(),
						'device' => $device->getId(),
						'channel' => $channel->getId(),
						'state' => $event->getGet()->toArray(),
					],
				),
			);

		} elseif ($device->getType() === Entities\Devices\ThirdPartyDevice::TYPE) {
			if ($this->thirdPartyDeviceHelper->getGatewayIdentifier($device) === null) {
				$this->queue->append(
					$this->messageBuilder->create(
						StoreDeviceConnectionState::class,
						[
							'connector' => $device->getConnector(),
							'identifier' => $device->getIdentifier(),
							'state' => MetadataTypes\ConnectionState::ALERT,
						],
					),
				);

				return;
			}

			$this->queue->append(
				$this->messageBuilder->create(
					WriteThirdPartyDeviceState::class,
					[
						'connector' => $device->getConnector(),
						'device' => $device->getId(),
						'channel' => $channel->getId(),
						'state' => $event->getRead()->toArray(),
					],
				),
			);
		}
	}

}
