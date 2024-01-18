<?php declare(strict_types = 1);

/**
 * WriteDevicePropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           22.11.23
 */

namespace FastyBird\Connector\Virtual\Queue\Consumers;

use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Drivers;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Helpers;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
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
final class WriteDevicePropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Queue\Queue $queue,
		private readonly Drivers\DriversManager $driversManager,
		private readonly Helpers\Entity $entityHelper,
		private readonly Virtual\Logger $logger,
		private readonly DevicesModels\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\States\DevicePropertiesManager $devicePropertiesStatesManager,
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
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\WriteDevicePropertyState) {
			return false;
		}

		$findConnectorQuery = new DevicesQueries\Configuration\FindConnectors();
		$findConnectorQuery->byId($entity->getConnector());

		$connector = $this->connectorsConfigurationRepository->findOneBy($findConnectorQuery);

		if ($connector === null) {
			$this->logger->error(
				'Connector could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_VIRTUAL,
					'type' => 'write-device-property-state-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'id' => $entity->getDevice()->toString(),
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
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_VIRTUAL,
					'type' => 'write-device-property-state-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'id' => $entity->getDevice()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byId($entity->getProperty());

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy($findDevicePropertyQuery);

		if (
			!$property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty
			&& !$property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty
		) {
			$this->logger->error(
				'Device property could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_VIRTUAL,
					'type' => 'write-device-property-state-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'id' => $entity->getDevice()->toString(),
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
			$property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty
			&& !$property->isSettable()
		) {
			$this->logger->error(
				'Device property is not writable',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_VIRTUAL,
					'type' => 'write-device-property-state-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'id' => $entity->getDevice()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$state = $property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty
			? $this->devicePropertiesStatesManager->get($property)
			: $this->devicePropertiesStatesManager->read($property);

		if ($state === null) {
			return true;
		}

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
			$valueToWrite = $state->getExpectedValue();
		} else {
			$valueToWrite = $state->getExpectedValue() ?? ($state->isValid() ? $state->getActualValue() : null);
		}

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
			$this->devicePropertiesStatesManager->setPendingState($property, $valueToWrite !== null);
		}

		if ($valueToWrite === null) {
			return true;
		}

		try {
			$driver = $this->driversManager->getDriver($device);

			$result = $property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty
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

			if ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
				$this->devicePropertiesStatesManager->setPendingState($property, false);
			}

			$this->logger->error(
				'Device is not properly configured',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_VIRTUAL,
					'type' => 'write-device-property-state-message-consumer',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'id' => $entity->getDevice()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$result->then(
			function () use ($property): void {
				if ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
					$state = $this->devicePropertiesStatesManager->get($property);

					if ($state?->getExpectedValue() !== null) {
						$this->devicePropertiesStatesManager->setPendingState($property, true);
					}
				}
			},
			function (Throwable $ex) use ($connector, $device, $property, $entity): void {
				if ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
					$this->devicePropertiesStatesManager->setPendingState($property, false);
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
						'source' => MetadataTypes\ConnectorSource::CONNECTOR_VIRTUAL,
						'type' => 'write-device-property-state-message-consumer',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $entity->getConnector()->toString(),
						],
						'device' => [
							'id' => $entity->getDevice()->toString(),
						],
						'property' => [
							'id' => $entity->getProperty()->toString(),
						],
						'data' => $entity->toArray(),
					],
				);
			},
		);

		$this->logger->debug(
			'Consumed write device state message',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_VIRTUAL,
				'type' => 'write-device-property-state-message-consumer',
				'connector' => [
					'id' => $entity->getConnector()->toString(),
				],
				'device' => [
					'id' => $entity->getDevice()->toString(),
				],
				'property' => [
					'id' => $entity->getProperty()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
