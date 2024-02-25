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
use FastyBird\Connector\NsPanel\Exceptions;
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
	 * @throws Exceptions\InvalidArgument
	 * @throws DBAL\Exception
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreSubDevice) {
			return false;
		}

		$parent = $this->devicesRepository->find($message->getGateway(), Entities\Devices\Gateway::class);

		if ($parent === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'store-sub-device-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'gateway' => [
						'id' => $message->getGateway()->toString(),
					],
					'device' => [
						'identifier' => $message->getIdentifier(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$findDeviceQuery = new Queries\Entities\FindSubDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->forParent($parent);
		$findDeviceQuery->byIdentifier($message->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\SubDevice::class);

		if ($device === null) {
			$connector = $this->connectorsRepository->find(
				$message->getConnector(),
				Entities\Connectors\Connector::class,
			);

			if ($connector === null) {
				$this->logger->error(
					'Connector could not be loaded',
					[
						'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
						'type' => 'store-sub-device-message-consumer',
						'connector' => [
							'id' => $message->getConnector()->toString(),
						],
						'gateway' => [
							'id' => $message->getGateway()->toString(),
						],
						'device' => [
							'identifier' => $message->getIdentifier(),
						],
						'data' => $message->toArray(),
					],
				);

				return true;
			}

			$device = $this->databaseHelper->transaction(
				function () use ($message, $connector, $parent): Entities\Devices\SubDevice {
					$device = $this->devicesManager->create(Utils\ArrayHash::from([
						'entity' => Entities\Devices\SubDevice::class,
						'connector' => $connector,
						'parent' => $parent,
						'identifier' => $message->getIdentifier(),
						'name' => $message->getName(),
					]));
					assert($device instanceof Entities\Devices\SubDevice);

					return $device;
				},
			);

			$this->logger->info(
				'Sub-device was created',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'store-sub-device-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'gateway' => [
						'id' => $message->getGateway()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
						'identifier' => $message->getIdentifier(),
						'protocol' => $message->getProtocol(),
					],
				],
			);
		}

		$this->setDeviceProperty(
			$device->getId(),
			$message->getManufacturer(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::MANUFACTURER,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MANUFACTURER->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getModel(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::MODEL,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MODEL->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getFirmwareVersion(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::FIRMWARE_VERSION,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::FIRMWARE_VERSION->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getDisplayCategory()->value,
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::CATEGORY,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::CATEGORY->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getMacAddress(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::MAC_ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MAC_ADDRESS->value),
		);

		foreach ($message->getCapabilities() as $capability) {
			$this->databaseHelper->transaction(function () use ($message, $device, $capability): bool {
				$identifier = Helpers\Name::convertCapabilityToChannel($capability->getCapability());

				if (
					$capability->getCapability() === Types\Capability::TOGGLE
					&& $capability->getName() !== null
				) {
					$identifier .= '_' . $capability->getName();
				}

				$findChannelQuery = new Queries\Entities\FindChannels();
				$findChannelQuery->byIdentifier($identifier);
				$findChannelQuery->forDevice($device);

				$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Channel::class);

				if ($channel === null) {
					$channel = $this->channelsManager->create(Utils\ArrayHash::from([
						'entity' => Entities\Channels\Channel::class,
						'device' => $device,
						'identifier' => $identifier,
					]));

					$this->logger->debug(
						'Device channel was created',
						[
							'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
							'type' => 'store-sub-device-message-consumer',
							'connector' => [
								'id' => $message->getConnector()->toString(),
							],
							'gateway' => [
								'id' => $message->getGateway()->toString(),
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

		foreach ($message->getTags() as $tag => $value) {
			if ($tag === Types\Capability::TOGGLE->value && is_array($value)) {
				$this->databaseHelper->transaction(function () use ($message, $device, $value): void {
					foreach ($value as $key => $name) {
						$findChannelQuery = new Queries\Entities\FindChannels();
						$findChannelQuery->byIdentifier(
							Helpers\Name::convertCapabilityToChannel(
								Types\Capability::TOGGLE,
								$key,
							),
						);
						$findChannelQuery->forDevice($device);

						$channel = $this->channelsRepository->findOneBy(
							$findChannelQuery,
							Entities\Channels\Channel::class,
						);

						if ($channel !== null) {
							$channel = $this->channelsManager->update($channel, Utils\ArrayHash::from([
								'name' => $name,
							]));

							$this->logger->debug(
								'Toggle channel name was set',
								[
									'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
									'type' => 'store-sub-device-message-consumer',
									'connector' => [
										'id' => $message->getConnector()->toString(),
									],
									'gateway' => [
										'id' => $message->getGateway()->toString(),
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
				'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
				'type' => 'store-sub-device-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'gateway' => [
					'id' => $message->getGateway()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

}
