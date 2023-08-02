<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           16.07.23
 */

namespace FastyBird\Connector\NsPanel\Clients;

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Consumers;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Connector\NsPanel\Writers;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Ramsey\Uuid;
use React\Promise;
use Throwable;
use function array_key_exists;
use function array_map;
use function array_merge;
use function assert;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;

/**
 * Third-party device client
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device implements Client
{

	use TPropertiesMapper;
	use Nette\SmartObject;

	private API\LanApi $lanApiApi;

	public function __construct(
		protected readonly Helpers\Property $propertyStateHelper,
		protected readonly DevicesModels\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly Entities\NsPanelConnector $connector,
		private readonly Consumers\Messages $consumer,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		private readonly Writers\Writer $writer,
		private readonly NsPanel\Logger $logger,
		API\LanApiFactory $lanApiApiFactory,
	)
	{
		$this->lanApiApi = $lanApiApiFactory->create(
			$this->connector->getIdentifier(),
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\LanApiCall
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function connect(): void
	{
		$findDevicesQuery = new Queries\FindGatewayDevices();
		$findDevicesQuery->forConnector($this->connector);

		foreach ($this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Gateway::class) as $gateway) {
			$ipAddress = $gateway->getIpAddress();
			$accessToken = $gateway->getAccessToken();

			if ($ipAddress === null || $accessToken === null) {
				continue;
			}

			$findDevicesQuery = new Queries\FindThirdPartyDevices();
			$findDevicesQuery->forConnector($this->connector);
			$findDevicesQuery->forParent($gateway);

			/** @var array<Entities\Devices\ThirdPartyDevice> $devices */
			$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\ThirdPartyDevice::class);

			$syncDevices = array_map(
				function (Entities\Devices\ThirdPartyDevice $device): array {
					$capabilities = [];
					$statuses = [];
					$tags = [];

					foreach ($device->getChannels() as $channel) {
						assert($channel instanceof Entities\NsPanelChannel);

						$capabilities[] = [
							'capability' => $channel->getCapability()->getValue(),
							'permission' => Types\Permission::get(
								$channel->getCapability()->hasReadWritePermission() ? Types\Permission::READ_WRITE : Types\Permission::READ,
							)->getValue(),
							'name' => $channel->getIdentifier(),
						];

						$status = $this->mapChannelToStatus($channel);

						if ($status !== null) {
							$statuses = array_merge($statuses, $status);
						}

						foreach ($channel->getProperties() as $property) {
							if (
								$property instanceof DevicesEntities\Channels\Properties\Variable
								&& is_string($property->getValue())
								&& preg_match(
									NsPanel\Constants::TAG_PROPERTY_IDENTIFIER,
									$property->getIdentifier(),
									$matches,
								) === 1
								&& array_key_exists('tag', $matches)
							) {
								$tags[$matches['tag']] = $property->getValue();
							}
						}

						if ($channel->getCapability()->equalsValue(Types\Capability::TOGGLE)) {
							if (!array_key_exists('toggle', $tags)) {
								$tags['toggle'] = [];
							}

							if (is_array($tags['toggle'])) {
								$tags['toggle'][$channel->getIdentifier()] = $channel->getName() ?? $channel->getIdentifier();
							}
						}
					}

					return [
						'third_serial_number' => $device->getPlainId(),
						'name' => $device->getName() ?? $device->getIdentifier(),
						'display_category' => $device->getDisplayCategory()->getValue(),
						'capabilities' => $capabilities,
						'state' => $statuses,
						'tags' => $tags,
						'manufacturer' => $device->getManufacturer(),
						'model' => $device->getModel(),
						'firmware_version' => $device->getFirmwareVersion(),
						'service_address' => sprintf(
							'http://%s:%d/do-directive/%s',
							Helpers\Network::getLocalAddress(),
							$device->getConnector()->getPort(),
							$device->getPlainId(),
						),
						'online' => true, // Virtual device is always online
					];
				},
				$devices,
			);

			foreach ($devices as $device) {
				$this->consumer->append(
					new Entities\Messages\DeviceState(
						$this->connector->getId(),
						$device->getIdentifier(),
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_RUNNING),
					),
				);
			}

			$this->lanApiApi->synchroniseDevices(
				$syncDevices,
				$ipAddress,
				$accessToken,
			)
				->then(function () use ($gateway): void {
					$this->logger->debug(
						'NS Panel third-party devices was successfully synchronised',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
							'type' => 'device-client',
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
							'gateway' => [
								'id' => $gateway->getPlainId(),
							],
						],
					);
				})
				->otherwise(function (Throwable $ex) use ($gateway): void {
					$this->logger->error(
						'Could not synchronise third-party devices with NS Panel',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
							'type' => 'device-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
							'gateway' => [
								'id' => $gateway->getPlainId(),
							],
						],
					);
				});

			$this->lanApiApi->getSubDevices($ipAddress, $accessToken)
				->then(
					function (Entities\API\Response\GetSubDevices $response) use ($gateway, $ipAddress, $accessToken): void {
						foreach ($response->getData()->getDevicesList() as $subDevice) {
							if ($subDevice->getThirdSerialNumber() !== null) {
								$findDevicesQuery = new Queries\FindThirdPartyDevices();
								$findDevicesQuery->forParent($gateway);
								$findDevicesQuery->byId(Uuid\Uuid::fromString($subDevice->getThirdSerialNumber()));

								$device = $this->devicesRepository->findOneBy(
									$findDevicesQuery,
									Entities\Devices\ThirdPartyDevice::class,
								);

								if ($device === null) {
									$this->lanApiApi->removeDevice(
										$subDevice->getSerialNumber(),
										$ipAddress,
										$accessToken,
									)
										->then(function () use ($gateway, $subDevice): void {
											$this->logger->debug(
												'Removed third-party from NS Panel',
												[
													'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
													'type' => 'device-client',
													'connector' => [
														'id' => $this->connector->getPlainId(),
													],
													'gateway' => [
														'id' => $gateway->getPlainId(),
													],
													'device' => [
														'id' => $subDevice->getThirdSerialNumber(),
													],
												],
											);
										})
										->otherwise(function (Throwable $ex) use ($gateway, $subDevice): void {
											$this->logger->error(
												'Could not remove deleted third-party device from NS Panel',
												[
													'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
													'type' => 'device-client',
													'exception' => BootstrapHelpers\Logger::buildException($ex),
													'connector' => [
														'id' => $this->connector->getPlainId(),
													],
													'gateway' => [
														'id' => $gateway->getPlainId(),
													],
													'device' => [
														'id' => $subDevice->getThirdSerialNumber(),
													],
												],
											);
										});
								}
							}
						}
					},
				)
				->otherwise(function (Throwable $ex) use ($gateway): void {
					$this->logger->error(
						'Could not fetch NS Panel registered devices',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
							'type' => 'device-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
							'gateway' => [
								'id' => $gateway->getPlainId(),
							],
						],
					);
				});
		}

		$this->writer->connect($this->connector, $this);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\LanApiCall
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function disconnect(): void
	{
		$this->writer->disconnect($this->connector, $this);

		$findDevicesQuery = new Queries\FindGatewayDevices();
		$findDevicesQuery->forConnector($this->connector);

		foreach ($this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Gateway::class) as $gateway) {
			$ipAddress = $gateway->getIpAddress();
			$accessToken = $gateway->getAccessToken();

			if ($ipAddress === null || $accessToken === null) {
				continue;
			}

			$findDevicesQuery = new Queries\FindThirdPartyDevices();
			$findDevicesQuery->forConnector($this->connector);
			$findDevicesQuery->forParent($gateway);

			foreach ($this->devicesRepository->findAllBy(
				$findDevicesQuery,
				Entities\Devices\ThirdPartyDevice::class,
			) as $device) {
				try {
					$serialNumber = $device->getGatewayIdentifier();

					if ($serialNumber === null) {
						continue;
					}
				} catch (Throwable) {
					continue;
				}

				$this->lanApiApi->reportDeviceState(
					$serialNumber,
					false,
					$ipAddress,
					$accessToken,
				)
					->then(function () use ($gateway, $device): void {
						$this->logger->debug(
							'State for NS Panel third-party device was successfully updated',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
								'type' => 'device-client',
								'connector' => [
									'id' => $this->connector->getPlainId(),
								],
								'gateway' => [
									'id' => $gateway->getPlainId(),
								],
								'device' => [
									'id' => $device->getPlainId(),
								],
							],
						);
					})
					->otherwise(function (Throwable $ex) use ($gateway, $device): void {
						$this->logger->error(
							'State for NS Panel third-party device could not be updated',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
								'type' => 'device-client',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'connector' => [
									'id' => $this->connector->getPlainId(),
								],
								'gateway' => [
									'id' => $gateway->getPlainId(),
								],
								'device' => [
									'id' => $device->getPlainId(),
								],
							],
						);
					});

				$this->consumer->append(
					new Entities\Messages\DeviceState(
						$this->connector->getId(),
						$device->getIdentifier(),
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_STOPPED),
					),
				);
			}
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\LanApiCall
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeChannelProperty(
		Entities\NsPanelDevice $device,
		Entities\NsPanelChannel $channel,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped|MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelMappedProperty $property,
	): Promise\PromiseInterface
	{
		if (!$device instanceof Entities\Devices\ThirdPartyDevice) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Only third-party device could be updated'),
			);
		}

		if ($device->getGateway()->getIpAddress() === null || $device->getGateway()->getAccessToken() === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Device assigned NS Panel is not configured'),
			);
		}

		$state = $this->channelPropertiesStates->getValue($property);

		if ($state === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Property state could not be found. Nothing to write'),
			);
		}

		try {
			$serialNumber = $device->getGatewayIdentifier();

			if ($serialNumber === null) {
				return Promise\reject(new Exceptions\LanApiCall('Device gateway identifier is not configured'));
			}
		} catch (Throwable) {
			return Promise\reject(new Exceptions\LanApiCall('Could not get device gateway identifier'));
		}

		$status = $this->mapChannelToStatus($channel);

		if ($status === null) {
			return Promise\reject(new Exceptions\LanApiCall('Device capability status could not be created'));
		}

		return $this->lanApiApi->reportDeviceStatus(
			$serialNumber,
			$status,
			$device->getGateway()->getIpAddress(),
			$device->getGateway()->getAccessToken(),
		);
	}

}
