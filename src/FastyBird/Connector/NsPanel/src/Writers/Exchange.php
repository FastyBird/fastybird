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

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use React\EventLoop;

/**
 * Exchange based properties writer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Exchange extends Periodic implements Writer, ExchangeConsumers\Consumer
{

	public const NAME = 'exchange';

	/**
	 * @throws ExchangeExceptions\InvalidArgument
	 */
	public function __construct(
		MetadataDocuments\DevicesModule\Connector $connector,
		Helpers\Entity $entityHelper,
		Helpers\Devices\ThirdPartyDevice $thirdPartyDeviceHelper,
		Queue\Queue $queue,
		DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		DevicesUtilities\ChannelPropertiesStates $channelPropertiesStatesManager,
		DateTimeFactory\Factory $dateTimeFactory,
		EventLoop\LoopInterface $eventLoop,
		private readonly ExchangeConsumers\Container $consumer,
	)
	{
		parent::__construct(
			$connector,
			$entityHelper,
			$thirdPartyDeviceHelper,
			$queue,
			$devicesConfigurationRepository,
			$channelsConfigurationRepository,
			$channelsPropertiesConfigurationRepository,
			$channelPropertiesStatesManager,
			$dateTimeFactory,
			$eventLoop,
		);

		$this->consumer->register($this, null, false);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 */
	public function connect(): void
	{
		parent::connect();

		$this->consumer->enable(self::class);
	}

	/**
	 * @throws ExchangeExceptions\InvalidArgument
	 */
	public function disconnect(): void
	{
		parent::disconnect();

		$this->consumer->disable(self::class);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function consume(
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\AutomatorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataDocuments\Document|null $entity,
	): void
	{
		if (
			$entity instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
			|| $entity instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty
			|| $entity instanceof MetadataDocuments\DevicesModule\ChannelVariableProperty
		) {
			$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
			$findChannelQuery->byId($entity->getChannel());

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

			if (!$device->getConnector()->equals($this->connector->getId())) {
				return;
			}

			if ($device->getType() === Entities\Devices\SubDevice::TYPE) {
				if (
					(
						$entity instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
						|| $entity instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty
					)
					&& (
						$entity->getExpectedValue() === null
						|| $entity->getPending() !== true
					)
				) {
					return;
				}

				$this->writeSubDeviceChannel($device, $channel);

			} elseif ($device->getType() === Entities\Devices\ThirdPartyDevice::TYPE) {
				if (
					(
						$entity instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
						|| $entity instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty
					)
					&& $entity->isValid() !== true
				) {
					return;
				}

				$this->writeThirdPartyDeviceChannel($device, $channel);
			}
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
