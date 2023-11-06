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
use FastyBird\Connector\Virtual\Queries;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
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

	public function __construct(
		private readonly Queue\Queue $queue,
		private readonly Drivers\DriversManager $driversManager,
		private readonly Helpers\Entity $entityHelper,
		private readonly Virtual\Logger $logger,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStatesManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\WriteChannelPropertyState) {
			return false;
		}

		$findConnectorQuery = new Queries\FindConnectors();
		$findConnectorQuery->byId($entity->getConnector());

		$connector = $this->connectorsRepository->findOneBy($findConnectorQuery, Entities\VirtualConnector::class);

		if ($connector === null) {
			$this->logger->error(
				'Connector could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
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

		$findDeviceQuery = new Queries\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byId($entity->getDevice());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\VirtualDevice::class);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
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

		$findChannelQuery = new DevicesQueries\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byId($entity->getChannel());

		$channel = $this->channelsRepository->findOneBy($findChannelQuery);

		if ($channel === null) {
			$this->logger->error(
				'Channel could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
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

		$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byId($entity->getProperty());

		$property = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

		if (
			!$property instanceof DevicesEntities\Channels\Properties\Dynamic
			&& !$property instanceof DevicesEntities\Channels\Properties\Mapped
		) {
			$this->logger->error(
				'Channel property could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
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
			$property instanceof DevicesEntities\Channels\Properties\Dynamic
			&& !$property->isSettable()
		) {
			$this->logger->error(
				'Channel property is not writable',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
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

		$state = $this->channelPropertiesStatesManager->readValue($property);

		if ($state === null) {
			return true;
		}

		$valueToWrite = $property instanceof DevicesEntities\Channels\Properties\Mapped
			? $state->getActualValue()
			: $state->getExpectedValue();

		if ($property instanceof DevicesEntities\Channels\Properties\Mapped) {
			$valueToWrite = Helpers\Transformer::fromMappedParent($property, $valueToWrite);
		}

		$valueToWrite = DevicesUtilities\ValueHelper::normalizeValue(
			$property->getDataType(),
			$valueToWrite,
			$property->getFormat(),
			$property->getInvalid(),
		);

		try {
			$driver = $this->driversManager->getDriver($device);

			$result = $property instanceof DevicesEntities\Channels\Properties\Mapped
				? $driver->notifyState($property, $valueToWrite)
				: $driver->writeState($property, $valueToWrite);
		} catch (Exceptions\InvalidState $ex) {
			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $connector->getId()->toString(),
						'device' => $device->getId()->toString(),
						'state' => MetadataTypes\ConnectionState::STATE_ALERT,
					],
				),
			);

			$this->logger->error(
				'Device is not properly configured',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
					'type' => 'write-channel-property-state-message-consumer',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
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
			function () use ($property): void {
				if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
					$now = $this->dateTimeFactory->getNow();

					$state = $this->channelPropertiesStatesManager->getValue($property);

					if ($state?->getExpectedValue() !== null) {
						$this->channelPropertiesStatesManager->setValue(
							$property,
							Utils\ArrayHash::from([
								DevicesStates\Property::PENDING_KEY => $now->format(DateTimeInterface::ATOM),
							]),
						);
					}
				}
			},
			function (Throwable $ex) use ($connector, $device, $channel, $property, $entity): void {
				if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
					$this->channelPropertiesStatesManager->setValue(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::EXPECTED_VALUE_KEY => null,
							DevicesStates\Property::PENDING_KEY => false,
						]),
					);
				}

				$this->queue->append(
					$this->entityHelper->create(
						Entities\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $connector->getId()->toString(),
							'identifier' => $device->getIdentifier(),
							'state' => MetadataTypes\ConnectionState::STATE_ALERT,
						],
					),
				);

				$this->logger->error(
					'Could write state to device',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
						'type' => 'write-channel-property-state-message-consumer',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
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
			'Consumed write sub device state message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
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
