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
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use React\Promise;
use Throwable;
use function array_filter;
use function array_key_exists;
use function array_map;
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

			/** @var array<Entities\Devices\Device> $devices */
			$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Device::class);

			$syncDevices = array_map(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				function (Entities\Devices\Device $device): Entities\API\ThirdPartyDevice {
					$capabilities = [];
					$statuses = [];
					$tags = [];

					foreach ($device->getChannels() as $channel) {
						assert($channel instanceof Entities\NsPanelChannel);

						$capabilities[] = new Entities\API\Capability(
							$channel->getCapability(),
							Types\Permission::get(
								$channel->getCapability()->hasReadWritePermission() ? Types\Permission::READ_WRITE : Types\Permission::READ,
							),
							$channel->getIdentifier(),
						);

						$statuses[] = $this->mapChannelToStatus($channel);

						$statuses = array_filter(
							$statuses,
							static fn (Entities\API\Statuses\Status|null $value) => $value !== null,
						);

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

					return new Entities\API\ThirdPartyDevice(
						$device->getPlainId(),
						$device->getName() ?? $device->getIdentifier(),
						$device->getDisplayCategory(),
						$capabilities,
						$statuses,
						$tags,
						$device->getManufacturer(),
						$device->getModel(),
						$device->getFirmwareVersion(),
						sprintf(
							'http://%s:%d/do-directive/%s',
							Helpers\Network::getLocalAddress(),
							$device->getConnector()->getPort(),
							$device->getPlainId(),
						),
						true, // Virtual device is always online
					);
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
				Entities\Devices\Device::class,
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
		DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped $property,
	): Promise\PromiseInterface
	{
		if (!$device instanceof Entities\Devices\Device) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Only third-party device could be updated'),
			);
		}

		if ($device->getParent()->getIpAddress() === null || $device->getParent()->getAccessToken() === null) {
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
			$device->getParent()->getIpAddress(),
			$device->getParent()->getAccessToken(),
		);
	}

}