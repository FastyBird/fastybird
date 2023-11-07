<?php declare(strict_types = 1);

/**
 * Gateway.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           13.07.23
 */

namespace FastyBird\Connector\NsPanel\Clients;

use DateTimeInterface;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use React\EventLoop;
use Throwable;
use function array_key_exists;
use function in_array;
use function is_string;
use function preg_match;
use function strval;

/**
 * Connector gateway client
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Gateway implements Client
{

	use Nette\SmartObject;

	private const HANDLER_START_DELAY = 2.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const HEARTBEAT_DELAY = 600;

	private const CMD_STATE = 'state';

	private const CMD_HEARTBEAT = 'heartbeat';

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, array<string, DateTimeInterface|bool>> */
	private array $processedDevicesCommands = [];

	private API\LanApi $lanApi;

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		private readonly Entities\NsPanelConnector $connector,
		API\LanApiFactory $lanApiFactory,
		private readonly Queue\Queue $queue,
		private readonly Helpers\Entity $entityHelper,
		private readonly NsPanel\Logger $logger,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
		$this->lanApi = $lanApiFactory->create($this->connector->getIdentifier());
	}

	public function connect(): void
	{
		$this->processedDevices = [];
		$this->processedDevicesCommands = [];

		$this->handlerTimer = null;

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			function (): void {
				$this->registerLoopHandler();
			},
		);
	}

	public function disconnect(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function handleCommunication(): void
	{
		$findDevicesQuery = new Queries\Entities\FindGatewayDevices();
		$findDevicesQuery->forConnector($this->connector);

		foreach ($this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Gateway::class) as $device) {
			if (
				!in_array($device->getId()->toString(), $this->processedDevices, true)
				&& !$this->deviceConnectionManager->getState($device)->equalsValue(
					MetadataTypes\ConnectionState::STATE_ALERT,
				)
			) {
				$this->processedDevices[] = $device->getId()->toString();

				if ($this->processDevice($device)) {
					$this->registerLoopHandler();

					return;
				}
			}
		}

		$this->processedDevices = [];

		$this->registerLoopHandler();
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function processDevice(Entities\Devices\Gateway $device): bool
	{
		if ($this->readDeviceInformation($device)) {
			return true;
		}

		return $this->readDeviceState($device);
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function readDeviceInformation(Entities\Devices\Gateway $gateway): bool
	{
		if (
			$gateway->getIpAddress() === null
			|| $gateway->getAccessToken() === null
		) {
			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $this->connector->getId()->toString(),
						'identifier' => $gateway->getIdentifier(),
						'state' => MetadataTypes\ConnectionState::STATE_ALERT,
					],
				),
			);

			return true;
		}

		if (!array_key_exists($gateway->getId()->toString(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$gateway->getId()->toString()] = [];
		}

		if (array_key_exists(self::CMD_HEARTBEAT, $this->processedDevicesCommands[$gateway->getId()->toString()])) {
			$cmdResult = $this->processedDevicesCommands[$gateway->getId()->toString()][self::CMD_HEARTBEAT];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp() < self::HEARTBEAT_DELAY
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$gateway->getId()->toString()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();

		try {
			$this->lanApi->getGatewayInfo($gateway->getIpAddress())
				->then(function () use ($gateway): void {
					$this->processedDevicesCommands[$gateway->getId()->toString()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();

					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $this->connector->getId()->toString(),
								'identifier' => $gateway->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::STATE_CONNECTED,
							],
						),
					);
				})
				->otherwise(function (Throwable $ex) use ($gateway): void {
					if ($ex instanceof Exceptions\LanApiCall) {
						$this->logger->error(
							'Could not NS Panel API',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
								'type' => 'gateway-client',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
								'device' => [
									'id' => $gateway->getId()->toString(),
								],
								'request' => [
									'method' => $ex->getRequest()?->getMethod(),
									'url' => $ex->getRequest() !== null ? strval($ex->getRequest()->getUri()) : null,
									'body' => $ex->getRequest()?->getBody()->getContents(),
								],
								'response' => [
									'body' => $ex->getResponse()?->getBody()->getContents(),
								],
							],
						);

						$this->queue->append(
							$this->entityHelper->create(
								Entities\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $this->connector->getId()->toString(),
									'identifier' => $gateway->getIdentifier(),
									'state' => MetadataTypes\ConnectionState::STATE_DISCONNECTED,
								],
							),
						);

						return;
					}

					$this->logger->error(
						'Calling NS Panel API failed',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
							'type' => 'gateway-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
							'device' => [
								'id' => $gateway->getId()->toString(),
							],
						],
					);

					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $this->connector->getId()->toString(),
								'identifier' => $gateway->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::STATE_LOST,
							],
						),
					);
				});
		} catch (Throwable $ex) {
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'gateway-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'device' => [
						'id' => $gateway->getId()->toString(),
					],
				],
			);

			return false;
		}

		return true;
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function readDeviceState(Entities\Devices\Gateway $gateway): bool
	{
		if (
			$gateway->getIpAddress() === null
			|| $gateway->getAccessToken() === null
		) {
			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $this->connector->getId()->toString(),
						'identifier' => $gateway->getIdentifier(),
						'state' => MetadataTypes\ConnectionState::STATE_ALERT,
					],
				),
			);

			return true;
		}

		if (!array_key_exists($gateway->getId()->toString(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$gateway->getId()->toString()] = [];
		}

		if (array_key_exists(self::CMD_STATE, $this->processedDevicesCommands[$gateway->getId()->toString()])) {
			$cmdResult = $this->processedDevicesCommands[$gateway->getId()->toString()][self::CMD_STATE];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp() < $gateway->getStateReadingDelay()
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$gateway->getId()->toString()][self::CMD_STATE] = $this->dateTimeFactory->getNow();

		try {
			$this->lanApi->getSubDevices($gateway->getIpAddress(), $gateway->getAccessToken())
				->then(function (Entities\API\Response\GetSubDevices $subDevices) use ($gateway): void {
					$this->processedDevicesCommands[$gateway->getId()->toString()][self::CMD_STATE] = $this->dateTimeFactory->getNow();

					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $this->connector->getId()->toString(),
								'identifier' => $gateway->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::STATE_CONNECTED,
							],
						),
					);

					foreach ($subDevices->getData()->getDevicesList() as $subDevice) {
						// Ignore third-party devices
						if ($subDevice->getThirdSerialNumber() !== null) {
							continue;
						}

						$this->queue->append(
							$this->entityHelper->create(
								Entities\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $this->connector->getId()->toString(),
									'identifier' => $subDevice->getSerialNumber(),
									'state' => $subDevice->isOnline()
										? MetadataTypes\ConnectionState::STATE_CONNECTED
										: MetadataTypes\ConnectionState::STATE_DISCONNECTED,
								],
							),
						);

						$state = [];

						foreach ($subDevice->getState() as $key => $item) {
							$identifier = null;

							if (
								is_string($key)
								&& preg_match(NsPanel\Constants::STATE_NAME_KEY, $key, $matches) === 1
								&& array_key_exists('identifier', $matches)
							) {
								$identifier = $matches['identifier'];
							}

							foreach ($item->getProtocols() as $protocol => $value) {
								$state[] = [
									'capability' => $item->getType()->getValue(),
									'protocol' => $protocol,
									'value' => $value,
									'identifier' => $identifier,
								];
							}
						}

						$this->queue->append(
							$this->entityHelper->create(
								Entities\Messages\StoreDeviceState::class,
								[
									'connector' => $this->connector->getId()->toString(),
									'gateway' => $gateway->getId()->toString(),
									'identifier' => $subDevice->getSerialNumber(),
									'state' => $state,
								],
							),
						);
					}
				})
				->otherwise(function (Throwable $ex) use ($gateway): void {
					if ($ex instanceof Exceptions\LanApiCall) {
						$this->logger->warning(
							'Calling NS Panel API failed',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
								'type' => 'gateway-client',
								'error' => $ex->getMessage(),
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
								'device' => [
									'id' => $gateway->getId()->toString(),
								],
								'request' => [
									'method' => $ex->getRequest()?->getMethod(),
									'url' => $ex->getRequest() !== null ? strval($ex->getRequest()->getUri()) : null,
									'body' => $ex->getRequest()?->getBody()->getContents(),
								],
								'response' => [
									'body' => $ex->getResponse()?->getBody()->getContents(),
								],
							],
						);

						$this->queue->append(
							$this->entityHelper->create(
								Entities\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $this->connector->getId()->toString(),
									'identifier' => $gateway->getIdentifier(),
									'state' => MetadataTypes\ConnectionState::STATE_DISCONNECTED,
								],
							),
						);

						return;
					}

					$this->logger->error(
						'Calling NS Panel API failed',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
							'type' => 'gateway-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
							'device' => [
								'id' => $gateway->getId()->toString(),
							],
						],
					);

					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $this->connector->getId()->toString(),
								'identifier' => $gateway->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::STATE_LOST,
							],
						),
					);
				});
		} catch (Throwable $ex) {
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'gateway-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'device' => [
						'id' => $gateway->getId()->toString(),
					],
				],
			);

			return false;
		}

		return true;
	}

	private function registerLoopHandler(): void
	{
		$this->handlerTimer = $this->eventLoop->addTimer(
			self::HANDLER_PROCESSING_INTERVAL,
			function (): void {
				$this->handleCommunication();
			},
		);
	}

}
