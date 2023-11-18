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
use FastyBird\Connector\Shelly\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use React\EventLoop;
use Symfony\Component\EventDispatcher;

/**
 * Event based properties writer
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Event extends Periodic implements Writer, EventDispatcher\EventSubscriberInterface
{

	public const NAME = 'event';

	/**
	 * @param DevicesModels\Configuration\Devices\Repository<MetadataDocuments\DevicesModule\Device> $devicesRepository
	 * @param DevicesModels\Configuration\Channels\Repository<MetadataDocuments\DevicesModule\Channel> $channelsRepository
	 * @param DevicesModels\Configuration\Channels\Properties\Repository<MetadataDocuments\DevicesModule\ChannelDynamicProperty> $channelsPropertiesRepository
	 */
	public function __construct(
		MetadataDocuments\DevicesModule\Connector $connector,
		Helpers\Entity $entityHelper,
		Queue\Queue $queue,
		DevicesModels\Configuration\Devices\Repository $devicesRepository,
		DevicesModels\Configuration\Channels\Repository $channelsRepository,
		DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesRepository,
		DevicesUtilities\ChannelPropertiesStates $channelPropertiesStatesManager,
		DateTimeFactory\Factory $dateTimeFactory,
		EventLoop\LoopInterface $eventLoop,
	)
	{
		parent::__construct(
			$connector,
			$entityHelper,
			$queue,
			$devicesRepository,
			$channelsRepository,
			$channelsPropertiesRepository,
			$channelPropertiesStatesManager,
			$dateTimeFactory,
			$eventLoop,
		);
	}

	public static function getSubscribedEvents(): array
	{
		return [
			DevicesEvents\ChannelPropertyStateEntityCreated::class => 'stateChanged',
			DevicesEvents\ChannelPropertyStateEntityUpdated::class => 'stateChanged',
		];
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function stateChanged(
		DevicesEvents\ChannelPropertyStateEntityCreated|DevicesEvents\ChannelPropertyStateEntityUpdated $event,
	): void
	{
		$property = $event->getProperty();

		$state = $event->getState();

		if ($state->getExpectedValue() === null || $state->getPending() !== true) {
			return;
		}

		$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
		$findChannelQuery->byId($property->getChannel());

		$channel = $this->channelsRepository->findOneBy($findChannelQuery);

		if ($channel === null) {
			return;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byId($channel->getDevice());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			return;
		}

		if (!$device->getConnector()->equals($this->connector->getId())) {
			return;
		}

		$this->queue->append(
			$this->entityHelper->create(
				Entities\Messages\WriteChannelPropertyState::class,
				[
					'connector' => $device->getConnector()->toString(),
					'device' => $device->getId()->toString(),
					'channel' => $channel->getId()->toString(),
					'property' => $property->getId()->toString(),
				],
			),
		);
	}

}
