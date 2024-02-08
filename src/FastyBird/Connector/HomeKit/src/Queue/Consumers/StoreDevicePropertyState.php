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
use FastyBird\Connector\HomeKit\Queue;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use Nette\Utils;
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
		private readonly HomeKit\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly ApplicationHelpers\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreDevicePropertyState) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byId($message->getDevice());

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::HOMEKIT,
					'type' => 'store-device-property-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byId($message->getProperty());

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy($findDevicePropertyQuery);

		if ($property === null) {
			$this->logger->error(
				'Device device property could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::HOMEKIT,
					'type' => 'store-device-property-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceVariableProperty) {
			$this->databaseHelper->transaction(
				function () use ($message, $property): void {
					$property = $this->devicesPropertiesRepository->find(
						$property->getId(),
						DevicesEntities\Devices\Properties\Variable::class,
					);
					assert($property instanceof DevicesEntities\Devices\Properties\Variable);

					$this->devicesPropertiesManager->update(
						$property,
						Utils\ArrayHash::from([
							'value' => $message->getValue(),
						]),
					);
				},
			);

		} elseif ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
			await($this->devicePropertiesStatesManager->set(
				$property,
				Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_FIELD => $message->getValue(),
				]),
				MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::HOMEKIT),
			));
		} elseif ($property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty) {
			$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceProperties();
			$findDevicePropertyQuery->byId($property->getParent());

			$parent = $this->devicesPropertiesConfigurationRepository->findOneBy($findDevicePropertyQuery);

			if ($parent instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
				await($this->devicePropertiesStatesManager->write(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::EXPECTED_VALUE_FIELD => $message->getValue(),
					]),
					MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::HOMEKIT),
				));
			} elseif ($parent instanceof MetadataDocuments\DevicesModule\DeviceVariableProperty) {
				$this->databaseHelper->transaction(function () use ($message, $parent, $device, $property): void {
					$toUpdate = $this->devicesPropertiesRepository->find(
						$parent->getId(),
						DevicesEntities\Devices\Properties\Variable::class,
					);

					if ($toUpdate !== null) {
						$this->devicesPropertiesManager->update(
							$toUpdate,
							Utils\ArrayHash::from([
								'value' => $message->getValue(),
							]),
						);
					} else {
						$this->logger->error(
							'Mapped variable property could not be updated',
							[
								'source' => MetadataTypes\Sources\Connector::HOMEKIT,
								'type' => 'store-device-property-state-message-consumer',
								'connector' => [
									'id' => $message->getConnector()->toString(),
								],
								'device' => [
									'id' => $device->getId()->toString(),
								],
								'property' => [
									'id' => $property->getId()->toString(),
								],
								'data' => $message->toArray(),
							],
						);
					}
				});
			}
		}

		$this->logger->debug(
			'Consumed store device state message',
			[
				'source' => MetadataTypes\Sources\Connector::HOMEKIT,
				'type' => 'store-device-property-state-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'property' => [
					'id' => $property->getId()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

}
