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
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
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
		$property = $event->getProperty();

		$state = $event->getState();

		$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
		$findChannelQuery->byId($property->getChannel());

		$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

		if ($channel === null) {
			return;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byId($channel->getDevice());

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			return;
		}

		if ($device->getType() === Entities\Devices\SubDevice::TYPE) {
			if ($state->getExpectedValue() === null || $state->getPending() !== true) {
				return;
			}

			$this->writeSubDeviceChannel($device, $channel);

		} elseif ($device->getType() === Entities\Devices\ThirdPartyDevice::TYPE) {
			if ($state->isValid() !== true) {
				return;
			}

			$this->writeThirdPartyDeviceChannel($device, $channel);
		}
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function writeSubDeviceChannel(
		MetadataDocuments\DevicesModule\Device $device,
		MetadataDocuments\DevicesModule\Channel $channel,
	): void
	{
		$this->queue->append(
			$this->entityHelper->create(
				Entities\Messages\WriteSubDeviceState::class,
				[
					'connector' => $device->getConnector(),
					'device' => $device->getId(),
					'channel' => $channel->getId(),
				],
			),
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeThirdPartyDeviceChannel(
		MetadataDocuments\DevicesModule\Device $device,
		MetadataDocuments\DevicesModule\Channel $channel,
	): void
	{
		if ($this->thirdPartyDeviceHelper->getGatewayIdentifier($device) === null) {
			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $device->getConnector(),
						'identifier' => $device->getIdentifier(),
						'state' => MetadataTypes\ConnectionState::STATE_ALERT,
					],
				),
			);

			return;
		}

		$this->queue->append(
			$this->entityHelper->create(
				Entities\Messages\WriteThirdPartyDeviceState::class,
				[
					'connector' => $device->getConnector(),
					'device' => $device->getId(),
					'channel' => $channel->getId(),
				],
			),
		);
	}

}
