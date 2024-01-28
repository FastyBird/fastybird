<?php declare(strict_types = 1);

/**
 * WriteV1DevicePropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           03.12.23
 */

namespace FastyBird\Connector\FbMqtt\Queue\Consumers;

use DateTimeInterface;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\API;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use RuntimeException;
use Throwable;
use function strval;

/**
 * Write V1 protocol state to device message consumer
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WriteV1DevicePropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly API\ConnectionManager $connectionManager,
		private readonly Helpers\Connector $connectorHelper,
		private readonly FbMqtt\Logger $logger,
		private readonly DevicesModels\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\WriteDevicePropertyState) {
			return false;
		}

		$findConnectorQuery = new DevicesQueries\Configuration\FindConnectors();
		$findConnectorQuery->byId($entity->getConnector());
		$findConnectorQuery->byType(Entities\FbMqttConnector::TYPE);

		$connector = $this->connectorsConfigurationRepository->findOneBy($findConnectorQuery);

		if ($connector === null) {
			$this->logger->error(
				'Connector could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::FB_MQTT,
					'type' => 'write-v1-property-state-message-consumer',
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

		if (!$this->connectorHelper->getProtocolVersion($connector)->equalsValue(
			FbMqtt\Types\ProtocolVersion::VERSION_1,
		)) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byId($entity->getDevice());
		$findDeviceQuery->byType(Entities\FbMqttDevice::TYPE);

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::FB_MQTT,
					'type' => 'write-v1-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
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

		$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceDynamicProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byId($entity->getProperty());

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
		);

		if ($property === null) {
			$this->logger->error(
				'Device property could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::FB_MQTT,
					'type' => 'write-v1-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		if (!$property->isSettable()) {
			$this->logger->warning(
				'Property is not writable',
				[
					'source' => MetadataTypes\ConnectorSource::FB_MQTT,
					'type' => 'write-v1-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
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

		$expectedValue = MetadataUtilities\Value::flattenValue($state->getExpectedValue());

		if ($expectedValue === null) {
			$this->devicePropertiesStatesManager->setPendingState(
				$property,
				false,
				MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::FB_MQTT),
			);

			return true;
		}

		$now = $this->dateTimeFactory->getNow();
		$pending = $state->getPending();

		if (
			$pending === false
			|| (
				$pending instanceof DateTimeInterface
				&& (float) $now->format('Uv') - (float) $pending->format('Uv') <= FbMqtt\Constants::WRITE_DEBOUNCE_DELAY
			)
		) {
			return true;
		}

		$this->devicePropertiesStatesManager->setPendingState(
			$property,
			true,
			MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::FB_MQTT),
		);

		$topic = API\V1Builder::buildDevicePropertyTopic($device, $property);

		$this->connectionManager
			->getConnection($connector)
			->publish(
				$topic,
				strval($expectedValue),
			)
			->then(function () use ($connector, $device, $property, $entity): void {
				$this->logger->debug(
					'Channel state was successfully sent to device',
					[
						'source' => MetadataTypes\ConnectorSource::FB_MQTT,
						'type' => 'write-v1-property-state-message-consumer',
						'connector' => [
							'id' => $connector->getId()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
						],
						'property' => [
							'id' => $property->getId()->toString(),
						],
						'data' => $entity->toArray(),
					],
				);
			})
			->catch(function (Throwable $ex) use ($connector, $device, $property, $entity): void {
				$this->devicePropertiesStatesManager->setPendingState(
					$property,
					false,
					MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::FB_MQTT),
				);

				$this->logger->error(
					'Could write state to device',
					[
						'source' => MetadataTypes\ConnectorSource::FB_MQTT,
						'type' => 'write-v1-property-state-message-consumer',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $connector->getId()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
						],
						'property' => [
							'id' => $property->getId()->toString(),
						],
						'data' => $entity->toArray(),
					],
				);
			});

		$this->logger->debug(
			'Consumed write device state message',
			[
				'source' => MetadataTypes\ConnectorSource::FB_MQTT,
				'type' => 'write-v1-property-state-message-consumer',
				'connector' => [
					'id' => $connector->getId()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
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
