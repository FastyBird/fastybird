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

use DateTimeInterface;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Connector\NsPanel\Writers;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Psr\Log;
use React\Promise;
use Throwable;
use function array_map;
use function assert;
use function boolval;
use function intval;
use function is_string;
use function sprintf;
use function strval;

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

	use Nette\SmartObject;

	private API\LanApi $lanApiApi;

	public function __construct(
		private readonly Entities\NsPanelConnector $connector,
		private readonly Helpers\Property $propertyStateHelper,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		private readonly Writers\Writer $writer,
		API\LanApiFactory $lanApiApiFactory,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
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
		$findDevicesQuery = new DevicesQueries\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		foreach ($this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Gateway::class) as $gateway) {
			assert($gateway instanceof Entities\Devices\Gateway);

			$ipAddress = $gateway->getIpAddress();
			$accessToken = $gateway->getAccessToken();

			if ($ipAddress === null || $accessToken === null) {
				continue;
			}

			$findDevicesQuery = new DevicesQueries\FindDevices();
			$findDevicesQuery->forConnector($this->connector);
			$findDevicesQuery->forParent($gateway);

			/** @var array<Entities\Devices\Device> $devices */
			$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Device::class);

			$syncDevices = array_map(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				function (Entities\Devices\Device $device): Entities\API\ThirdPartyDevice {
					$capabilities = [];
					$states = [];
					$tags = [];

					foreach ($device->getChannels() as $channel) {
						foreach ($channel->getProperties() as $property) {
							if (
								$property instanceof DevicesEntities\Channels\Properties\Variable
								&& is_string($property->getValue())
							) {
								$tags[$property->getIdentifier()] = $property->getValue();

							} elseif ($property instanceof DevicesEntities\Channels\Properties\Mapped) {
								$capabilities[] = new Entities\API\Capability(
									Types\Capability::get($property->getIdentifier()),
									Types\Permission::get(
										$property->isSettable() ? Types\Permission::READ_WRITE : Types\Permission::READ,
									),
									$channel->getName(),
								);

								$states[] = $this->mapPropertyToState(
									$property,
									$this->propertyStateHelper->getActualValue($property),
								);
							}
						}
					}

					return new Entities\API\ThirdPartyDevice(
						$device->getPlainId(),
						$device->getName() ?? $device->getIdentifier(),
						$device->getDisplayCategory(),
						$capabilities,
						$states,
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
						true,
					);
				},
				$devices,
			);

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

		$findDevicesQuery = new DevicesQueries\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		foreach ($this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Gateway::class) as $gateway) {
			assert($gateway instanceof Entities\Devices\Gateway);

			$ipAddress = $gateway->getIpAddress();
			$accessToken = $gateway->getAccessToken();

			if ($ipAddress === null || $accessToken === null) {
				continue;
			}

			$findDevicesQuery = new DevicesQueries\FindDevices();
			$findDevicesQuery->forConnector($this->connector);
			$findDevicesQuery->forParent($gateway);

			foreach ($this->devicesRepository->findAllBy(
				$findDevicesQuery,
				Entities\Devices\Device::class,
			) as $device) {
				assert($device instanceof Entities\Devices\Device);

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
					->then(function () use ($gateway): void {
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
							],
						);
					})
					->otherwise(function (Throwable $ex) use ($gateway): void {
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
							],
						);
					});
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
		DevicesEntities\Channels\Channel $channel,
		DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped $property,
	): Promise\PromiseInterface
	{
		if (!$device instanceof Entities\Devices\Device) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Only third-party device could be updated'),
			);
		}

		if (!$property instanceof DevicesEntities\Channels\Properties\Mapped) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Only dynamic properties could be updated'),
			);
		}

		if ($device->getParent()->getIpAddress() === null || $device->getParent()->getAccessToken() === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Device assigned gateway is not configured'),
			);
		}

		$state = $this->channelPropertiesStates->getValue($property);

		if ($state === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Property state could not be found. Nothing to write'),
			);
		}

		$expectedValue = DevicesUtilities\ValueHelper::flattenValue($state->getExpectedValue());

		if (!$property->isSettable()) {
			return Promise\reject(new Exceptions\InvalidArgument('Provided property is not writable'));
		}

		if ($expectedValue === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Property expected value is not set. Nothing to write'),
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

		if ($state->isPending() === true) {
			return $this->lanApiApi->reportDeviceStatus(
				$serialNumber,
				$this->mapPropertyToState($property, $expectedValue),
				$device->getParent()->getIpAddress(),
				$device->getParent()->getAccessToken(),
			);
		}

		return Promise\reject(new Exceptions\InvalidArgument('Provided property state is in invalid state'));
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function mapPropertyToState(
		DevicesEntities\Channels\Properties\Mapped $property,
		bool|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\CoverPayload|MetadataTypes\SwitchPayload|float|int|string|null $value = null,
	): Entities\API\Statuses\Status
	{
		$value = API\Transformer::transformValueToDevice(
			$property->getDataType(),
			$property->getFormat(),
			$value,
		);

		switch ($property->getIdentifier()) {
			case Types\Capability::POWER:
				return new Entities\API\Statuses\Power(Types\PowerPayload::get($value));
			case Types\Capability::TOGGLE:
				return new Entities\API\Statuses\Toggle(
					$property->getChannel()->getIdentifier(),
					Types\TogglePayload::get($value),
				);
			case Types\Capability::BRIGHTNESS:
				return new Entities\API\Statuses\Brightness(intval($value));
			case Types\Capability::COLOR_TEMPERATURE:
				return new Entities\API\Statuses\ColorTemperature(intval($value));
			case Types\Capability::COLOR_RGB:
				return new Entities\API\Statuses\ColorRgb(0, 0, 0);
			case Types\Capability::PERCENTAGE:
				return new Entities\API\Statuses\Percentage(intval($value));
			case Types\Capability::MOTOR_CONTROL:
				return new Entities\API\Statuses\MotorControl(Types\MotorControlPayload::get($value));
			case Types\Capability::MOTOR_REVERSE:
				return new Entities\API\Statuses\MotorReverse(boolval($value));
			case Types\Capability::MOTOR_CALIBRATION:
				return new Entities\API\Statuses\MotorCalibration(Types\MotorCalibrationPayload::get($value));
			case Types\Capability::STARTUP:
				return new Entities\API\Statuses\Startup(
					Types\StartupPayload::get($value),
					$property->getChannel()->getIdentifier(),
				);
			case Types\Capability::CAMERA_STREAM:
				return new Entities\API\Statuses\CameraStream(strval($value));
			case Types\Capability::DETECT:
				return new Entities\API\Statuses\Detect(boolval($value));
			case Types\Capability::HUMIDITY:
				return new Entities\API\Statuses\Humidity(intval($value));
			case Types\Capability::TEMPERATURE:
				return new Entities\API\Statuses\Temperature(intval($value));
			case Types\Capability::BATTERY:
				return new Entities\API\Statuses\Battery(intval($value));
			case Types\Capability::PRESS:
				return new Entities\API\Statuses\Press(Types\PressPayload::get($value));
			case Types\Capability::RSSI:
				return new Entities\API\Statuses\Rssi(intval($value));
		}

		throw new Exceptions\InvalidArgument('Provided property type is not supported');
	}

}
