<?php declare(strict_types = 1);

/**
 * WriteDevicePropertyState.php
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

use FastyBird\Connector\HomeKit;
use FastyBird\Connector\HomeKit\Clients;
use FastyBird\Connector\HomeKit\Documents;
use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Protocol;
use FastyBird\Connector\HomeKit\Queries;
use FastyBird\Connector\HomeKit\Queue;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use RuntimeException;
use TypeError;
use ValueError;
use function intval;

/**
 * Write state to device message consumer
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WriteDevicePropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Protocol\Driver $accessoryDriver,
		private readonly Clients\Subscriber $subscriber,
		private readonly HomeKit\Logger $logger,
		private readonly DevicesModels\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws RuntimeException
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\WriteDevicePropertyState) {
			return false;
		}

		$findConnectorQuery = new Queries\Configuration\FindConnectors();
		$findConnectorQuery->byId($message->getConnector());

		$connector = $this->connectorsConfigurationRepository->findOneBy(
			$findConnectorQuery,
			Documents\Connectors\Connector::class,
		);

		if ($connector === null) {
			$this->logger->error(
				'Connector could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::HOMEKIT->value,
					'type' => 'write-device-property-state-message-consumer',
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

		$findDeviceQuery = new Queries\Configuration\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byId($message->getDevice());

		$device = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\Device::class,
		);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::HOMEKIT->value,
					'type' => 'write-device-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
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

		$accessory = $this->accessoryDriver->findAccessory($device->getId());

		if (!$accessory instanceof Protocol\Accessories\Generic) {
			$this->logger->warning(
				'Accessory for received device property message was not found in accessory driver',
				[
					'source' => MetadataTypes\Sources\Connector::HOMEKIT->value,
					'type' => 'write-device-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
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

		$property = $this->devicesPropertiesConfigurationRepository->find($message->getProperty());

		if ($property === null) {
			$this->logger->error(
				'Device property could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::HOMEKIT->value,
					'type' => 'write-device-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
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

		$characteristicService = $updatedCharacteristic = null;

		foreach ($accessory->getServices() as $service) {
			foreach ($service->getCharacteristics() as $characteristic) {
				if (
					$characteristic->getProperty() === null
					|| !$characteristic->getProperty()->getId()->equals($property->getId())
				) {
					continue;
				}

				$characteristicService = $service;
				$updatedCharacteristic = $characteristic;

				if ($property instanceof DevicesDocuments\Devices\Properties\Mapped) {
					$parent = $this->devicesPropertiesConfigurationRepository->find($property->getParent());

					if ($parent instanceof DevicesDocuments\Devices\Properties\Dynamic) {
						if ($message->getState() !== null) {
							$characteristic->setActualValue($message->getState()->getActualValue());
							$characteristic->setExpectedValue($message->getState()->getExpectedValue());
							$characteristic->setValid($message->getState()->isValid());
						} else {
							$this->logger->warning(
								'State entity is missing in event entity',
								[
									'source' => MetadataTypes\Sources\Connector::HOMEKIT->value,
									'type' => 'write-device-property-state-message-consumer',
									'connector' => [
										'id' => $connector->getId()->toString(),
									],
									'device' => [
										'id' => $device->getId()->toString(),
									],
									'property' => [
										'id' => $property->getId()->toString(),
									],
									'hap' => $accessory->toHap(),
								],
							);

							return true;
						}
					} elseif ($parent instanceof DevicesDocuments\Devices\Properties\Variable) {
						$characteristic->setActualValue($parent->getValue());
						$characteristic->setValid(true);
					}
				} elseif ($property instanceof DevicesDocuments\Devices\Properties\Dynamic) {
					if ($message->getState() !== null) {
						$characteristic->setActualValue($message->getState()->getActualValue());
						$characteristic->setExpectedValue($message->getState()->getExpectedValue());
						$characteristic->setValid($message->getState()->isValid());
					} else {
						$this->logger->warning(
							'State entity is missing in event entity',
							[
								'source' => MetadataTypes\Sources\Connector::HOMEKIT->value,
								'type' => 'write-device-property-state-message-consumer',
								'connector' => [
									'id' => $connector->getId()->toString(),
								],
								'device' => [
									'id' => $device->getId()->toString(),
								],
								'property' => [
									'id' => $property->getId()->toString(),
								],
								'hap' => $accessory->toHap(),
							],
						);

						continue;
					}
				} elseif ($property instanceof DevicesDocuments\Devices\Properties\Variable) {
					$characteristic->setActualValue($property->getValue());
					$characteristic->setValid(true);
				}

				break 2;
			}
		}

		$characteristicService?->recalculateCharacteristics($updatedCharacteristic);

		foreach ($characteristicService?->getCharacteristics() ?? [] as $characteristic) {
			$this->subscriber->publish(
				intval($accessory->getAid()),
				intval($accessory->getIidManager()->getIid($characteristic)),
				Protocol\Transformer::toClient(
					$characteristic->getProperty(),
					$characteristic->getDataType(),
					$characteristic->getValidValues(),
					$characteristic->getMaxLength(),
					$characteristic->getMinValue(),
					$characteristic->getMaxValue(),
					$characteristic->getMinStep(),
					$characteristic->isValid() ? $characteristic->getValue() : $characteristic->getDefault(),
				),
				$characteristic->immediateNotify(),
			);
		}

		$this->logger->debug(
			'Consumed write device state message',
			[
				'source' => MetadataTypes\Sources\Connector::HOMEKIT->value,
				'type' => 'write-device-property-state-message-consumer',
				'connector' => [
					'id' => $connector->getId()->toString(),
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
