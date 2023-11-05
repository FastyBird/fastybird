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
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use Symfony\Component\EventDispatcher;
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

	public function __construct(
		private readonly Entities\NsPanelConnector $connector,
		private readonly Helpers\Entity $entityHelper,
		private readonly Queue\Queue $queue,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
	)
	{
	}

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

		if ($property->getChannel() instanceof DevicesEntities\Channels\Channel) {
			$channel = $property->getChannel();

		} else {
			$findChannelQuery = new Queries\FindChannels();
			$findChannelQuery->byId($property->getChannel());

			$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\NsPanelChannel::class);
		}

		if ($channel === null) {
			return;
		}

		$device = $channel->getDevice();

		if (!$device->getConnector()->getId()->equals($this->connector->getId())) {
			return;
		}

		if ($device instanceof Entities\Devices\SubDevice) {
			assert($channel instanceof Entities\NsPanelChannel);

			if ($state->getExpectedValue() === null || $state->getPending() !== true) {
				return;
			}

			$this->writeSubDeviceChannelProperty($device, $channel);

		} elseif ($device instanceof Entities\Devices\ThirdPartyDevice) {
			assert($channel instanceof Entities\NsPanelChannel);

			if ($state->isValid() !== true) {
				return;
			}

			$this->writeThirdPartyDeviceChannelProperty($device, $channel);
		}
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function writeSubDeviceChannelProperty(
		Entities\Devices\SubDevice $device,
		Entities\NsPanelChannel $channel,
	): void
	{
		$this->queue->append(
			$this->entityHelper->create(
				Entities\Messages\WriteSubDeviceState::class,
				[
					'connector' => $this->connector->getId()->toString(),
					'device' => $device->getId()->toString(),
					'channel' => $channel->getId()->toString(),
				],
			),
		);
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeThirdPartyDeviceChannelProperty(
		Entities\Devices\ThirdPartyDevice $device,
		Entities\NsPanelChannel $channel,
	): void
	{
		if ($device->getGatewayIdentifier() === null) {
			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $this->connector->getId()->toString(),
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
					'connector' => $this->connector->getId()->toString(),
					'device' => $device->getId()->toString(),
					'channel' => $channel->getId()->toString(),
				],
			),
		);
	}

}
