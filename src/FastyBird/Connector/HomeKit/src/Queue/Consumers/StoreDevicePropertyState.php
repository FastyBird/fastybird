<?php declare(strict_types = 1);

/**
 * StoreDevicePropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           30.11.23
 */

namespace FastyBird\Connector\HomeKit\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\HomeKit;
use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Queue;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Exchange\Documents as ExchangeEntities;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use Throwable;
use function assert;
use function React\Async\await;

/**
 * Store device property state message consumer
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDevicePropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly bool $useExchange,
		private readonly HomeKit\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly DevicesUtilities\Database $databaseHelper,
		private readonly ExchangeEntities\DocumentFactory $entityFactory,
		private readonly ExchangePublisher\Async\Publisher $publisher,
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws Throwable
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\StoreDevicePropertyState) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byId($entity->getDevice());
		$findDeviceQuery->byType(Entities\HomeKitDevice::TYPE);

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_HOMEKIT,
					'type' => 'store-device-property-state-message-consumer',
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

		if ($property === null) {
			$this->logger->error(
				'Device device property could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_HOMEKIT,
					'type' => 'store-device-property-state-message-consumer',
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

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceVariableProperty) {
			$this->databaseHelper->transaction(
				function () use ($entity, $property): void {
					$property = $this->devicesPropertiesRepository->find(
						$property->getId(),
						DevicesEntities\Devices\Properties\Variable::class,
					);
					assert($property instanceof DevicesEntities\Devices\Properties\Variable);

					$this->devicesPropertiesManager->update(
						$property,
						Utils\ArrayHash::from([
							'value' => $entity->getValue(),
						]),
					);
				},
			);

		} elseif ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
			$this->devicePropertiesStatesManager->set(
				$property,
				Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_FIELD => $entity->getValue(),
				]),
			);
		} elseif ($property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty) {
			$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceProperties();
			$findDevicePropertyQuery->byId($property->getParent());

			$parent = $this->devicesPropertiesConfigurationRepository->findOneBy($findDevicePropertyQuery);

			if ($parent instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
				try {
					if ($this->useExchange) {
						await($this->publisher->publish(
							MetadataTypes\ConnectorSource::get(
								MetadataTypes\ConnectorSource::CONNECTOR_HOMEKIT,
							),
							MetadataTypes\RoutingKey::get(
								MetadataTypes\RoutingKey::DEVICE_PROPERTY_ACTION,
							),
							$this->entityFactory->create(
								Utils\Json::encode([
									'action' => MetadataTypes\PropertyAction::SET,
									'device' => $device->getId()->toString(),
									'property' => $property->getId()->toString(),
									'expected_value' => $this->devicePropertiesStatesManager->normalizePublishValue(
										$property,
										$entity->getValue(),
									),
								]),
								MetadataTypes\RoutingKey::get(
									MetadataTypes\RoutingKey::DEVICE_PROPERTY_ACTION,
								),
							),
						));
					} else {
						$this->devicePropertiesStatesManager->write(
							$property,
							Utils\ArrayHash::from([
								DevicesStates\Property::EXPECTED_VALUE_FIELD => $entity->getValue(),
							]),
						);
					}
				} catch (DevicesExceptions\InvalidState | Utils\JsonException | MetadataExceptions\InvalidValue $ex) {
					$this->logger->warning(
						'State value could not be converted to mapped parent',
						[
							'source' => MetadataTypes\ConnectorSource::CONNECTOR_HOMEKIT,
							'type' => 'store-device-property-state-message-consumer',
							'exception' => ApplicationHelpers\Logger::buildException($ex),
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
			} elseif ($parent instanceof MetadataDocuments\DevicesModule\DeviceVariableProperty) {
				$this->databaseHelper->transaction(function () use ($entity, $parent): void {
					$property = $this->devicesPropertiesRepository->find(
						$parent->getId(),
						DevicesEntities\Devices\Properties\Variable::class,
					);

					if ($property !== null) {
						$this->devicesPropertiesManager->update(
							$property,
							Utils\ArrayHash::from([
								'value' => $entity->getValue(),
							]),
						);
					} else {
						$this->logger->error(
							'Mapped variable property could not be updated',
							[
								'source' => MetadataTypes\ConnectorSource::CONNECTOR_HOMEKIT,
								'type' => 'store-device-property-state-message-consumer',
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
					}
				});
			}
		}

		$this->logger->debug(
			'Consumed store device state message',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_HOMEKIT,
				'type' => 'store-device-property-state-message-consumer',
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
