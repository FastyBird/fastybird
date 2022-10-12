<?php declare(strict_types = 1);

/**
 * Status.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Consumers
 * @since          0.37.0
 *
 * @date           20.07.22
 */

namespace FastyBird\ShellyConnector\Consumers\Messages;

use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\DevicesModule\Utilities as DevicesModuleUtilities;
use FastyBird\Metadata;
use FastyBird\ShellyConnector\Consumers\Consumer;
use FastyBird\ShellyConnector\Entities;
use FastyBird\ShellyConnector\Helpers;
use FastyBird\ShellyConnector\Mappers;
use Nette;
use Nette\Utils;
use Psr\Log;

/**
 * Device status message consumer
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Status implements Consumer
{

	use Nette\SmartObject;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DevicesModuleModels\DataStorage\DevicesRepository $devicesDataStorageRepository,
		private readonly DevicesModuleModels\States\DeviceConnectionStateManager $deviceConnectionStateManager,
		private readonly Mappers\Sensor $sensorMapper,
		private readonly Helpers\Property $propertyStateHelper,
		Log\LoggerInterface|null $logger,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws Metadata\Exceptions\FileNotFound
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\DeviceStatus) {
			return false;
		}

		$deviceItem = $this->devicesDataStorageRepository->findByIdentifier(
			$entity->getConnector(),
			$entity->getIdentifier(),
		);

		if ($deviceItem === null) {
			return true;
		}

		// Check device state...
		if (
			!$this->deviceConnectionStateManager->getState($deviceItem)
				->equalsValue(Metadata\Types\ConnectionState::STATE_CONNECTED)
		) {
			// ... and if it is not ready, set it to ready
			$this->deviceConnectionStateManager->setState(
				$deviceItem,
				Metadata\Types\ConnectionState::get(Metadata\Types\ConnectionState::STATE_CONNECTED),
			);
		}

		foreach ($entity->getChannels() as $shellyChannel) {
			foreach ($shellyChannel->getSensors() as $sensor) {
				$property = $this->sensorMapper->findProperty(
					$entity->getConnector(),
					$entity->getIdentifier(),
					$sensor->getIdentifier(),
				);

				if ($property !== null) {
					$actualValue = DevicesModuleUtilities\ValueHelper::flattenValue(
						DevicesModuleUtilities\ValueHelper::normalizeValue(
							$property->getDataType(),
							$sensor->getValue(),
							$property->getFormat(),
							$property->getInvalid(),
						),
					);

					$this->propertyStateHelper->setValue($property, Utils\ArrayHash::from([
						'actualValue' => $actualValue,
						'valid' => true,
					]));
				}
			}
		}

		$this->logger->debug(
			'Consumed device status message',
			[
				'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
				'type' => 'status-message-consumer',
				'device' => [
					'id' => $deviceItem->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
