<?php declare(strict_types = 1);

/**
 * DeviceProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           05.02.22
 */

namespace FastyBird\Connector\FbMqtt\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
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
use function assert;
use function count;
use function sprintf;

/**
 * Device property MQTT message consumer
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceProperty implements Queue\Consumer
{

	use Nette\SmartObject;
	use TProperty;

	public function __construct(
		private readonly FbMqtt\Logger $logger,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesUtilities\DevicePropertiesStates $devicePropertiesStatesManager,
		private readonly DevicesUtilities\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws Exceptions\ParseMessage
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\DeviceProperty) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getDevice());
		$findDeviceQuery->byType(Entities\FbMqttDevice::TYPE);

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			$this->logger->error(
				sprintf('Device "%s" is not registered', $entity->getDevice()),
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
					'type' => 'device-property-message-consumer',
					'device' => [
						'identifier' => $entity->getDevice(),
					],
				],
			);

			return true;
		}

		$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier($entity->getProperty());

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy($findDevicePropertyQuery);

		if ($property === null) {
			$this->logger->error(
				sprintf('Property "%s" is not registered', $entity->getProperty()),
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
					'type' => 'device-property-message-consumer',
					'device' => [
						'identifier' => $entity->getDevice(),
					],
					'property' => [
						'identifier' => $entity->getProperty(),
					],
				],
			);

			return true;
		}

		if ($entity->getValue() !== FbMqtt\Constants::VALUE_NOT_SET) {
			if ($property instanceof MetadataDocuments\DevicesModule\DeviceVariableProperty) {
				$this->databaseHelper->transaction(function () use ($entity, $property): void {
					$property = $this->devicesPropertiesRepository->find($property->getId());
					assert($property instanceof DevicesEntities\Devices\Properties\Property);

					$this->devicesPropertiesManager->update(
						$property,
						Utils\ArrayHash::from([
							'value' => $entity->getValue(),
						]),
					);
				});
			} elseif ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
				$this->devicePropertiesStatesManager->setValue(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::ACTUAL_VALUE_FIELD => $entity->getValue(),
						DevicesStates\Property::VALID_FIELD => true,
					]),
				);
			}
		} else {
			if (count($entity->getAttributes()) > 0) {
				$this->databaseHelper->transaction(function () use ($entity, $property): void {
					$property = $this->devicesPropertiesRepository->find($property->getId());
					assert($property instanceof DevicesEntities\Devices\Properties\Property);

					$toUpdate = $this->handlePropertyConfiguration($entity);

					if ($toUpdate !== []) {
						$this->devicesPropertiesManager->update($property, Utils\ArrayHash::from($toUpdate));
					}
				});
			}
		}

		$this->logger->debug(
			'Consumed channel property message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
				'type' => 'device-property-message-consumer',
				'device' => [
					'identifier' => $entity->getDevice(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
