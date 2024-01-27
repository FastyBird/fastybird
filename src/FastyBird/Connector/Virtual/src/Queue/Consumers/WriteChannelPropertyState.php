<?php declare(strict_types = 1);

/**
 * WriteChannelPropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           18.10.23
 */

namespace FastyBird\Connector\Virtual\Queue\Consumers;

use DateTimeInterface;
use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Drivers;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Helpers;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use RuntimeException;
use Throwable;

/**
 * Write state to device message consumer
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WriteChannelPropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	private const WRITE_PENDING_DELAY = 2_000.0;

	public function __construct(
		private readonly Queue\Queue $queue,
		private readonly Drivers\DriversManager $driversManager,
		private readonly Helpers\Entity $entityHelper,
		private readonly Virtual\Logger $logger,
		private readonly DevicesModels\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\WriteChannelPropertyState) {
			return false;
		}

		$findConnectorQuery = new DevicesQueries\Configuration\FindConnectors();
		$findConnectorQuery->byId($entity->getConnector());

		$connector = $this->connectorsConfigurationRepository->findOneBy($findConnectorQuery);

		if ($connector === null) {
			$this->logger->error(
				'Connector could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::VIRTUAL,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'id' => $entity->getDevice()->toString(),
					],
					'channel' => [
						'id' => $entity->getChannel()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byId($entity->getDevice());

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::VIRTUAL,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $entity->getDevice()->toString(),
					],
					'channel' => [
						'id' => $entity->getChannel()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byId($entity->getChannel());

		$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

		if ($channel === null) {
			$this->logger->error(
				'Channel could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::VIRTUAL,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $entity->getChannel()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byId($entity->getProperty());

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy($findChannelPropertyQuery);

		if (
			!$property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
			&& !$property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty
		) {
			$this->logger->error(
				'Channel property could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::VIRTUAL,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $channel->getId()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		if (
			$property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
			&& !$property->isSettable()
		) {
			$this->logger->error(
				'Channel property is not writable',
				[
					'source' => MetadataTypes\ConnectorSource::VIRTUAL,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $channel->getId()->toString(),
					],
					'property' => [
						'id' => $property->getId()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$state = $entity->getState();

		if ($state === null) {
			return true;
		}

		if ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
			$valueToWrite = $state->getExpectedValue();
		} else {
			$valueToWrite = $state->getExpectedValue() ?? ($state->isValid() ? $state->getActualValue() : null);
		}

		if ($valueToWrite === null) {
			if ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
				$this->channelPropertiesStatesManager->setPendingState($property, false);
			}

			return true;
		}

		$now = $this->dateTimeFactory->getNow();
		$pending = $state->getPending();

		if (
			$pending === false
			|| (
				$pending instanceof DateTimeInterface
				&& (float) $now->format('Uv') - (float) $pending->format('Uv') <= self::WRITE_PENDING_DELAY
			)
		) {
			return true;
		}

		if ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
			$this->channelPropertiesStatesManager->setPendingState($property, true);
		}

		try {
			$driver = $this->driversManager->getDriver($device);

			$result = $property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty
				? $driver->notifyState($property, $valueToWrite)
				: $driver->writeState($property, $valueToWrite);
		} catch (Exceptions\InvalidState $ex) {
			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $connector->getId()->toString(),
						'device' => $device->getId()->toString(),
						'state' => MetadataTypes\ConnectionState::ALERT,
					],
				),
			);

			if ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
				$this->channelPropertiesStatesManager->setPendingState($property, false);
			}

			$this->logger->error(
				'Device is not properly configured',
				[
					'source' => MetadataTypes\ConnectorSource::VIRTUAL,
					'type' => 'write-channel-property-state-message-consumer',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $channel->getId()->toString(),
					],
					'property' => [
						'id' => $property->getId()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$result->then(
			function () use ($connector, $device, $channel, $property, $entity): void {
				$this->logger->debug(
					'Channel state was successfully sent to device',
					[
						'source' => MetadataTypes\ConnectorSource::VIRTUAL,
						'type' => 'write-channel-property-state-message-consumer',
						'connector' => [
							'id' => $connector->getId()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
						],
						'channel' => [
							'id' => $channel->getId()->toString(),
						],
						'property' => [
							'id' => $property->getId()->toString(),
						],
						'data' => $entity->toArray(),
					],
				);
			},
			function (Throwable $ex) use ($connector, $device, $channel, $property, $entity): void {
				if ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
					$this->channelPropertiesStatesManager->setPendingState($property, false);
				}

				$this->queue->append(
					$this->entityHelper->create(
						Entities\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $connector->getId()->toString(),
							'identifier' => $device->getIdentifier(),
							'state' => MetadataTypes\ConnectionState::ALERT,
						],
					),
				);

				$this->logger->error(
					'Could write state to device',
					[
						'source' => MetadataTypes\ConnectorSource::VIRTUAL,
						'type' => 'write-channel-property-state-message-consumer',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $connector->getId()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
						],
						'channel' => [
							'id' => $channel->getId()->toString(),
						],
						'property' => [
							'id' => $property->getId()->toString(),
						],
						'data' => $entity->toArray(),
					],
				);
			},
		);

		$this->logger->debug(
			'Consumed write device state message',
			[
				'source' => MetadataTypes\ConnectorSource::VIRTUAL,
				'type' => 'write-channel-property-state-message-consumer',
				'connector' => [
					'id' => $connector->getId()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'channel' => [
					'id' => $channel->getId()->toString(),
				],
				'property' => [
					'id' => $property->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
