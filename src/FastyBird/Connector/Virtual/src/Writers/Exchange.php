<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           17.10.23
 */

namespace FastyBird\Connector\Virtual\Writers;

use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use React\EventLoop;
use function array_merge;

/**
 * Exchange based properties writer
 *
 * @package        FastyBird:VirtualConnector!
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
		Queue\MessageBuilder $messageBuilder,
		Queue\Queue $queue,
		DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		DevicesModels\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
		DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		DateTimeFactory\Factory $dateTimeFactory,
		EventLoop\LoopInterface $eventLoop,
		private readonly ExchangeConsumers\Container $consumer,
	)
	{
		parent::__construct(
			$connector,
			$messageBuilder,
			$queue,
			$devicesConfigurationRepository,
			$channelsConfigurationRepository,
			$devicesPropertiesConfigurationRepository,
			$channelsPropertiesConfigurationRepository,
			$devicePropertiesStatesManager,
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
	 */
	public function consume(
		MetadataTypes\Sources\Source $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataDocuments\Document|null $document,
	): void
	{
		if ($document instanceof MetadataDocuments\DevicesModule\DevicePropertyState) {
			$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
			$findDeviceQuery->forConnector($this->connector);
			$findDeviceQuery->byId($document->getDevice());

			$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

			if ($device === null) {
				return;
			}

			$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceProperties();
			$findPropertyQuery->byId($document->getId());
			$findPropertyQuery->forDevice($device);

			$property = $this->devicesPropertiesConfigurationRepository->findOneBy($findPropertyQuery);

			if ($property === null) {
				return;
			}

			if ($property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty) {
				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\WriteDevicePropertyState::class,
						[
							'connector' => $device->getConnector(),
							'device' => $device->getId(),
							'property' => $document->getId(),
							'state' => array_merge(
								$document->getRead()->toArray(),
								[
									'id' => $document->getId(),
									'valid' => $document->isValid(),
									'pending' => $document->getPending(),
								],
							),
						],
					),
				);
			} elseif ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\WriteDevicePropertyState::class,
						[
							'connector' => $device->getConnector(),
							'device' => $device->getId(),
							'property' => $document->getId(),
							'state' => array_merge(
								$document->getGet()->toArray(),
								[
									'id' => $document->getId(),
									'valid' => $document->isValid(),
									'pending' => $document->getPending(),
								],
							),
						],
					),
				);
			}
		} elseif ($document instanceof MetadataDocuments\DevicesModule\ChannelPropertyState) {
			$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
			$findChannelQuery->byId($document->getChannel());

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

			$findPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
			$findPropertyQuery->byId($document->getId());
			$findPropertyQuery->forChannel($channel);

			$property = $this->channelsPropertiesConfigurationRepository->findOneBy($findPropertyQuery);

			if ($property === null) {
				return;
			}

			if ($property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty) {
				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\WriteChannelPropertyState::class,
						[
							'connector' => $device->getConnector(),
							'device' => $device->getId(),
							'channel' => $channel->getId(),
							'property' => $document->getId(),
							'state' => array_merge(
								$document->getRead()->toArray(),
								[
									'id' => $document->getId(),
									'valid' => $document->isValid(),
									'pending' => $document->getPending(),
								],
							),
						],
					),
				);
			} elseif ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\WriteChannelPropertyState::class,
						[
							'connector' => $device->getConnector(),
							'device' => $device->getId(),
							'channel' => $channel->getId(),
							'property' => $document->getId(),
							'state' => array_merge(
								$document->getGet()->toArray(),
								[
									'id' => $document->getId(),
									'valid' => $document->isValid(),
									'pending' => $document->getPending(),
								],
							),
						],
					),
				);
			}
		}
	}

}
