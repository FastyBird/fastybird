<?php declare(strict_types = 1);

/**
 * State.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Consumers
 * @since          0.13.0
 *
 * @date           04.09.22
 */

namespace FastyBird\TuyaConnector\Consumers\Messages;

use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\Metadata;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use FastyBird\TuyaConnector\Consumers\Consumer;
use FastyBird\TuyaConnector\Entities;
use FastyBird\TuyaConnector\Helpers;
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
		private readonly DevicesModuleModels\DataStorage\DevicesRepository $devicesDataStorageRepository,
		private readonly DevicesModuleModels\DataStorage\DevicePropertiesRepository $devicePropertiesRepository,
		private readonly DevicesModuleModels\DataStorage\ChannelsRepository $channelsRepository,
		private readonly DevicesModuleModels\DataStorage\ChannelPropertiesRepository $channelPropertiesRepository,
		private readonly DevicesModuleModels\States\DeviceConnectionStateManager $deviceConnectionStateManager,
		Log\LoggerInterface|null $logger,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws MetadataExceptions\FileNotFound
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
			!$this->deviceConnectionStateManager->getState($deviceItem)->equals($actualDeviceState)
		) {
			// ... and if it is not ready, set it to ready
			$this->deviceConnectionStateManager->setState(
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
