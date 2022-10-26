<?php declare(strict_types = 1);

/**
 * State.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Consumers
 * @since          0.13.0
 *
 * @date           04.09.22
 */

namespace FastyBird\Connector\Tuya\Consumers\Messages;

use FastyBird\Connector\Tuya\Consumers\Consumer;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette;
use Psr\Log;

/**
 * Device state message consumer
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class State implements Consumer
{

	use Nette\SmartObject;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Helpers\Property $propertyStateHelper,
		private readonly DevicesModels\DataStorage\DevicesRepository $devicesDataStorageRepository,
		private readonly DevicesModels\DataStorage\DevicePropertiesRepository $devicePropertiesRepository,
		private readonly DevicesModels\DataStorage\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\DataStorage\ChannelPropertiesRepository $channelPropertiesRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		Log\LoggerInterface|null $logger,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\DeviceState) {
			return false;
		}

		$deviceItem = $this->devicesDataStorageRepository->findByIdentifier(
			$entity->getConnector(),
			$entity->getIdentifier(),
		);

		if ($deviceItem === null) {
			return true;
		}

		$actualDeviceState = Metadata\Types\ConnectionState::get(
			$entity->isOnline() ? Metadata\Types\ConnectionState::STATE_CONNECTED : Metadata\Types\ConnectionState::STATE_DISCONNECTED,
		);

		// Check device state...
		if (
			!$this->deviceConnectionManager->getState($deviceItem)->equals($actualDeviceState)
		) {
			// ... and if it is not ready, set it to ready
			$this->deviceConnectionManager->setState(
				$deviceItem,
				$actualDeviceState,
			);

			if ($actualDeviceState->equalsValue(Metadata\Types\ConnectionState::STATE_DISCONNECTED)) {
				$devicePropertiesItems = $this->devicePropertiesRepository->findAllByDevice(
					$deviceItem->getId(),
					MetadataEntities\DevicesModule\DeviceDynamicProperty::class,
				);

				foreach ($devicePropertiesItems as $propertyItem) {
					$this->propertyStateHelper->setValue(
						$propertyItem,
						Nette\Utils\ArrayHash::from([
							'valid' => false,
						]),
					);
				}

				$channelItems = $this->channelsRepository->findAllByDevice($deviceItem->getId());

				foreach ($channelItems as $channelItem) {
					$channelProperties = $this->channelPropertiesRepository->findAllByChannel(
						$channelItem->getId(),
						MetadataEntities\DevicesModule\ChannelDynamicProperty::class,
					);

					foreach ($channelProperties as $propertyItem) {
						$this->propertyStateHelper->setValue(
							$propertyItem,
							Nette\Utils\ArrayHash::from([
								'valid' => false,
							]),
						);
					}
				}
			}
		}

		$this->logger->debug(
			'Consumed device state message',
			[
				'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
				'type' => 'state-message-consumer',
				'device' => [
					'id' => $deviceItem->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
