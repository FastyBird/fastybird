<?php declare(strict_types = 1);

/**
 * StoreSubDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           24.08.22
 */

namespace FastyBird\Connector\NsPanel\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use function assert;
use function is_array;

/**
 * Store NS Panel sub-device message consumer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreSubDevice implements Queue\Consumer
{

	use DeviceProperty;
	use Nette\SmartObject;

	public function __construct(
		protected readonly NsPanel\Logger $logger,
		protected readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		protected readonly ApplicationHelpers\Database $databaseHelper,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\StoreSubDevice) {
			return false;
		}

		$parent = $this->devicesRepository->find($entity->getGateway(), Entities\Devices\Gateway::class);

		if ($parent === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL,
					'type' => 'store-sub-device-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'gateway' => [
						'id' => $entity->getGateway()->toString(),
					],
					'device' => [
						'identifier' => $entity->getIdentifier(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$findDeviceQuery = new Queries\Entities\FindSubDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->forParent($parent);
		$findDeviceQuery->byIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\SubDevice::class);

		if ($device === null) {
			$connector = $this->connectorsRepository->find(
				$entity->getConnector(),
				Entities\NsPanelConnector::class,
			);

			if ($connector === null) {
				$this->logger->error(
					'Connector could not be loaded',
					[
						'source' => MetadataTypes\Sources\Connector::NS_PANEL,
						'type' => 'store-sub-device-message-consumer',
						'connector' => [
							'id' => $entity->getConnector()->toString(),
						],
						'gateway' => [
							'id' => $entity->getGateway()->toString(),
						],
						'device' => [
							'identifier' => $entity->getIdentifier(),
						],
						'data' => $entity->toArray(),
					],
				);

				return true;
			}

			$device = $this->databaseHelper->transaction(
				function () use ($entity, $connector, $parent): Entities\Devices\SubDevice {
					$device = $this->devicesManager->create(Utils\ArrayHash::from([
						'entity' => Entities\Devices\SubDevice::class,
						'connector' => $connector,
						'parent' => $parent,
						'identifier' => $entity->getIdentifier(),
						'name' => $entity->getName(),
					]));
					assert($device instanceof Entities\Devices\SubDevice);

					return $device;
				},
			);

			$this->logger->info(
				'Sub-device was created',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL,
					'type' => 'store-sub-device-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'gateway' => [
						'id' => $entity->getGateway()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
						'identifier' => $entity->getIdentifier(),
						'protocol' => $entity->getProtocol(),
					],
				],
			);
		}

		$this->setDeviceProperty(
			$device->getId(),
			$entity->getManufacturer(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::STRING),
			Types\DevicePropertyIdentifier::MANUFACTURER,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MANUFACTURER),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getModel(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::STRING),
			Types\DevicePropertyIdentifier::MODEL,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MODEL),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getFirmwareVersion(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::STRING),
			Types\DevicePropertyIdentifier::FIRMWARE_VERSION,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::FIRMWARE_VERSION),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getDisplayCategory()->getValue(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::STRING),
			Types\DevicePropertyIdentifier::CATEGORY,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::CATEGORY),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getMacAddress(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::STRING),
			Types\DevicePropertyIdentifier::MAC_ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MAC_ADDRESS),
		);

		foreach ($entity->getCapabilities() as $capability) {
			$this->databaseHelper->transaction(function () use ($entity, $device, $capability): bool {
				$identifier = Helpers\Name::convertCapabilityToChannel($capability->getCapability());

				if (
					$capability->getCapability()->equalsValue(Types\Capability::TOGGLE)
					&& $capability->getName() !== null
				) {
					$identifier .= '_' . $capability->getName();
				}

				$findChannelQuery = new Queries\Entities\FindChannels();
				$findChannelQuery->byIdentifier($identifier);
				$findChannelQuery->forDevice($device);

				$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\NsPanelChannel::class);

				if ($channel === null) {
					$channel = $this->channelsManager->create(Utils\ArrayHash::from([
						'entity' => Entities\NsPanelChannel::class,
						'device' => $device,
						'identifier' => $identifier,
					]));

					$this->logger->debug(
						'Device channel was created',
						[
							'source' => MetadataTypes\Sources\Connector::NS_PANEL,
							'type' => 'store-sub-device-message-consumer',
							'connector' => [
								'id' => $entity->getConnector()->toString(),
							],
							'gateway' => [
								'id' => $entity->getGateway()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
							'channel' => [
								'id' => $channel->getId()->toString(),
							],
						],
					);
				}

				return true;
			});
		}

		foreach ($entity->getTags() as $tag => $value) {
			if ($tag === Types\Capability::TOGGLE && is_array($value)) {
				$this->databaseHelper->transaction(function () use ($entity, $device, $value): void {
					foreach ($value as $key => $name) {
						$findChannelQuery = new Queries\Entities\FindChannels();
						$findChannelQuery->byIdentifier(
							Helpers\Name::convertCapabilityToChannel(
								Types\Capability::get(Types\Capability::TOGGLE),
								$key,
							),
						);
						$findChannelQuery->forDevice($device);

						$channel = $this->channelsRepository->findOneBy(
							$findChannelQuery,
							Entities\NsPanelChannel::class,
						);

						if ($channel !== null) {
							$channel = $this->channelsManager->update($channel, Utils\ArrayHash::from([
								'name' => $name,
							]));

							$this->logger->debug(
								'Toggle channel name was set',
								[
									'source' => MetadataTypes\Sources\Connector::NS_PANEL,
									'type' => 'store-sub-device-message-consumer',
									'connector' => [
										'id' => $entity->getConnector()->toString(),
									],
									'gateway' => [
										'id' => $entity->getGateway()->toString(),
									],
									'device' => [
										'id' => $device->getId()->toString(),
									],
									'channel' => [
										'id' => $channel->getId()->toString(),
									],
								],
							);
						}
					}
				});
			}
		}

		$this->logger->debug(
			'Consumed store device message',
			[
				'source' => MetadataTypes\Sources\Connector::NS_PANEL,
				'type' => 'store-sub-device-message-consumer',
				'connector' => [
					'id' => $entity->getConnector()->toString(),
				],
				'gateway' => [
					'id' => $entity->getGateway()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
