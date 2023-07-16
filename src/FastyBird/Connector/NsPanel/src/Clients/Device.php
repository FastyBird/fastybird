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

use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
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
use function assert;

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

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Entities\NsPanelConnector $connector,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		private readonly Writers\Writer $writer,
		API\LanApiFactory $lanApiApiFactory,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();

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

			$this->lanApiApi->synchroniseDevices(
				$devices,
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

				$this->lanApiApi->reportDeviceState(
					$device,
					MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_STOPPED),
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
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\LanApiCall
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeChannelProperty(
		Entities\NsPanelDevice $device,
		DevicesEntities\Channels\Channel $channel,
		DevicesEntities\Channels\Properties\Dynamic $property,
	): Promise\PromiseInterface
	{
		if (!$device instanceof Entities\Devices\Device) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Only third-party device could be updated'),
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

		if ($state->isPending() === true) {
			return $this->lanApiApi->reportDeviceStatus(
				$device,
				$device->getParent()->getIpAddress(),
				$device->getParent()->getAccessToken(),
			);
		}

		return Promise\reject(new Exceptions\InvalidArgument('Provided property state is in invalid state'));
	}

}
