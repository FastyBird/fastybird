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
use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Models;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Psr\EventDispatcher as PsrEventDispatcher;
use React\EventLoop;
use Throwable;
use TypeError;
use ValueError;
use function array_key_exists;
use function in_array;
use function is_string;
use function preg_match;
use function React\Async\async;
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

	private const CMD_STATE = 'state';

	private const CMD_HEARTBEAT = 'heartbeat';

	/** @var array<string, Documents\Devices\Gateway>  */
	private array $devices = [];

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, array<string, DateTimeInterface|bool>> */
	private array $processedDevicesCommands = [];

	private API\LanApi $lanApi;

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly API\LanApiFactory $lanApiFactory,
		private readonly Queue\Queue $queue,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Helpers\Devices\Gateway $gatewayHelper,
		private readonly Models\StateRepository $stateRepository,
		private readonly NsPanel\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		$this->lanApi = $this->lanApiFactory->create($this->connector->getIdentifier());
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Mapping
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function connect(): void
	{
		$this->processedDevices = [];
		$this->processedDevicesCommands = [];

		$this->handlerTimer = null;

		$findDevicesQuery = new Queries\Configuration\FindGatewayDevices();
		$findDevicesQuery->forConnector($this->connector);
		$findDevicesQuery->withoutParents();

		$devices = $this->devicesConfigurationRepository->findAllBy(
			$findDevicesQuery,
			Documents\Devices\Gateway::class,
		);

		foreach ($devices as $device) {
			$this->devices[$device->getId()->toString()] = $device;

			$findChannelsQuery = new Queries\Configuration\FindChannels();
			$findChannelsQuery->forDevice($device);

			$channels = $this->channelsConfigurationRepository->findAllBy(
				$findChannelsQuery,
				Documents\Channels\Channel::class,
			);

			foreach ($channels as $channel) {
				$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
				$findChannelPropertiesQuery->forChannel($channel);

				$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
					$findChannelPropertiesQuery,
					DevicesDocuments\Channels\Properties\Dynamic::class,
				);

				foreach ($properties as $property) {
					$state = $this->channelPropertiesStatesManager->read(
						$property,
						MetadataTypes\Sources\Connector::NS_PANEL,
					);

					if ($state instanceof DevicesDocuments\States\Channels\Properties\Property) {
						$this->stateRepository->set($property->getId(), $state->getGet()->getActualValue());
					}
				}
			}
		}

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			async(function (): void {
				$this->registerLoopHandler();
			}),
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
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Mapping
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleCommunication(): void
	{
		foreach ($this->devices as $device) {
			if (!in_array($device->getId()->toString(), $this->processedDevices, true)) {
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
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Mapping
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function processDevice(Documents\Devices\Gateway $device): bool
	{
		if ($this->readDeviceInformation($device)) {
			return true;
		}

		return $this->readDeviceState($device);
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Mapping
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function readDeviceInformation(Documents\Devices\Gateway $gateway): bool
	{
		if (
			$this->gatewayHelper->getIpAddress($gateway) === null
			|| $this->gatewayHelper->getAccessToken($gateway) === null
		) {
			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $gateway->getConnector(),
						'identifier' => $gateway->getIdentifier(),
						'state' => DevicesTypes\ConnectionState::ALERT,
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
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp()
					< $this->gatewayHelper->getHeartbeatDelay($gateway)
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$gateway->getId()->toString()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();

		$deviceState = $this->deviceConnectionManager->getState($gateway);

		if ($deviceState === DevicesTypes\ConnectionState::ALERT) {
			unset($this->devices[$gateway->getId()->toString()]);

			return false;
		}

		try {
			$this->lanApi->getGatewayInfo($this->gatewayHelper->getIpAddress($gateway))
				->then(function () use ($gateway): void {
					$this->processedDevicesCommands[$gateway->getId()->toString()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();

					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $gateway->getConnector(),
								'identifier' => $gateway->getIdentifier(),
								'state' => DevicesTypes\ConnectionState::CONNECTED,
							],
						),
					);
				})
				->catch(function (Throwable $ex) use ($gateway): void {
					if ($ex instanceof Exceptions\LanApiError) {
						$this->logger->error(
							'Calling NS Panel API failed with error',
							[
								'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
								'type' => 'gateway-client',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
								'device' => [
									'id' => $gateway->getId()->toString(),
								],
							],
						);

						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $gateway->getConnector(),
									'identifier' => $gateway->getIdentifier(),
									'state' => DevicesTypes\ConnectionState::ALERT,
								],
							),
						);
					} elseif ($ex instanceof Exceptions\LanApiCall) {
						$this->logger->error(
							'Could not NS Panel API',
							[
								'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
								'type' => 'gateway-client',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
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
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $gateway->getConnector(),
									'identifier' => $gateway->getIdentifier(),
									'state' => DevicesTypes\ConnectionState::DISCONNECTED,
								],
							),
						);
					} else {
						$this->logger->error(
							'Calling NS Panel API failed',
							[
								'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
								'type' => 'gateway-client',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
								'device' => [
									'id' => $gateway->getId()->toString(),
								],
							],
						);

						$this->dispatcher?->dispatch(
							new DevicesEvents\TerminateConnector(
								MetadataTypes\Sources\Connector::NS_PANEL,
								'Unhandled error occur',
							),
						);
					}
				});
		} catch (Throwable $ex) {
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'gateway-client',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
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
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Mapping
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function readDeviceState(Documents\Devices\Gateway $gateway): bool
	{
		if (
			$this->gatewayHelper->getIpAddress($gateway) === null
			|| $this->gatewayHelper->getAccessToken($gateway) === null
		) {
			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $gateway->getConnector(),
						'identifier' => $gateway->getIdentifier(),
						'state' => DevicesTypes\ConnectionState::ALERT,
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
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp()
					< $this->gatewayHelper->getStateReadingDelay($gateway)
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$gateway->getId()->toString()][self::CMD_STATE] = $this->dateTimeFactory->getNow();

		$deviceState = $this->deviceConnectionManager->getState($gateway);

		if ($deviceState === DevicesTypes\ConnectionState::ALERT) {
			unset($this->devices[$gateway->getId()->toString()]);

			return false;
		}

		try {
			$this->lanApi->getSubDevices(
				$this->gatewayHelper->getIpAddress($gateway),
				$this->gatewayHelper->getAccessToken($gateway),
			)
				->then(function (API\Messages\Response\GetSubDevices $subDevices) use ($gateway): void {
					$this->processedDevicesCommands[$gateway->getId()->toString()][self::CMD_STATE] = $this->dateTimeFactory->getNow();

					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $gateway->getConnector(),
								'identifier' => $gateway->getIdentifier(),
								'state' => DevicesTypes\ConnectionState::CONNECTED,
							],
						),
					);

					foreach ($subDevices->getData()->getDevicesList() as $subDevice) {
						// Ignore third-party devices
						if ($subDevice->getThirdSerialNumber() !== null) {
							continue;
						}

						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $gateway->getConnector(),
									'identifier' => $subDevice->getSerialNumber(),
									'state' => $subDevice->isOnline()
										? DevicesTypes\ConnectionState::CONNECTED
										: DevicesTypes\ConnectionState::DISCONNECTED,
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
									'capability' => $item->getType()->value,
									'protocol' => $protocol,
									'value' => $value,
									'identifier' => $identifier,
								];
							}
						}

						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceState::class,
								[
									'connector' => $gateway->getConnector(),
									'gateway' => $gateway->getId(),
									'identifier' => $subDevice->getSerialNumber(),
									'state' => $state,
								],
							),
						);
					}
				})
				->catch(function (Throwable $ex) use ($gateway): void {
					if ($ex instanceof Exceptions\LanApiError) {
						$this->logger->error(
							'Calling NS Panel API failed with error',
							[
								'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
								'type' => 'gateway-client',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
								'device' => [
									'id' => $gateway->getId()->toString(),
								],
							],
						);

						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $gateway->getConnector(),
									'identifier' => $gateway->getIdentifier(),
									'state' => DevicesTypes\ConnectionState::ALERT,
								],
							),
						);
					} elseif ($ex instanceof Exceptions\LanApiCall) {
						$this->logger->warning(
							'Calling NS Panel API failed',
							[
								'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
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
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $gateway->getConnector(),
									'identifier' => $gateway->getIdentifier(),
									'state' => DevicesTypes\ConnectionState::DISCONNECTED,
								],
							),
						);
					} else {
						$this->logger->error(
							'Calling NS Panel API failed',
							[
								'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
								'type' => 'gateway-client',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
								'device' => [
									'id' => $gateway->getId()->toString(),
								],
							],
						);

						$this->dispatcher?->dispatch(
							new DevicesEvents\TerminateConnector(
								MetadataTypes\Sources\Connector::NS_PANEL,
								'Unhandled error occur',
							),
						);
					}
				});
		} catch (Throwable $ex) {
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'gateway-client',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
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
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Mapping
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function registerLoopHandler(): void
	{
		$this->handlerTimer = $this->eventLoop->addTimer(
			self::HANDLER_PROCESSING_INTERVAL,
			async(function (): void {
				$this->handleCommunication();
			}),
		);
	}

}
