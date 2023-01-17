<?php declare(strict_types = 1);

/**
 * Local.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Clients;

use DateTimeInterface;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Clients;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Connector\Shelly\Writers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use RuntimeException;
use Throwable;
use function array_filter;
use function array_key_exists;
use function assert;
use function in_array;

/**
 * Local devices client
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Local implements Client
{

	use Nette\SmartObject;

	private const HANDLER_START_DELAY = 2.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const CMD_STATUS = 'status';

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, array<string, DateTimeInterface|bool>> */
	private array $processedDevicesCommands = [];

	private Clients\Local\Coap|null $coapClient = null;

	private Clients\Local\Http|null $httpClient = null;

	private Clients\Local\Ws|null $wsClient = null;

	private EventLoop\TimerInterface|null $handlerTimer = null;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Entities\ShellyConnector $connector,
		private readonly API\Gen1Transformer $transformer,
		private readonly Clients\Local\CoapFactory $coapClientFactory,
		private readonly Clients\Local\HttpFactory $httpClientFactory,
		private readonly Clients\Local\WsFactory $wsClientFactory,
		private readonly Writers\Writer $writer,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 */
	public function connect(): void
	{
		$this->processedDevices = [];
		$this->processedDevicesCommands = [];

		$this->handlerTimer = null;

		$this->coapClient = $this->coapClientFactory->create($this->connector);
		$this->httpClient = $this->httpClientFactory->create();
		$this->wsClient = $this->wsClientFactory->create();

		try {
			$this->coapClient->connect();
		} catch (Throwable $ex) {
			$this->logger->error(
				'CoAP client could not be started',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'local-client',
					'group' => 'client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
				],
			);

			throw new DevicesExceptions\Terminate(
				'CoAP client could not be started',
				$ex->getCode(),
				$ex,
			);
		}

		try {
			$this->httpClient->connect();
		} catch (Throwable $ex) {
			$this->logger->error(
				'Http api client could not be started',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'local-client',
					'group' => 'client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
				],
			);

			throw new DevicesExceptions\Terminate(
				'Http api client could not be started',
				$ex->getCode(),
				$ex,
			);
		}

		try {
			$this->wsClient->connect(
				array_filter(
					$this->connector->getDevices(),
					static function (DevicesEntities\Devices\Device $device): bool {
						assert($device instanceof Entities\ShellyDevice);

						return $device->getGeneration()->equalsValue(Types\DeviceGeneration::GENERATION_2);
					},
				),
			);
		} catch (Throwable $ex) {
			$this->logger->error(
				'Websockets client could not be started',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'local-client',
					'group' => 'client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
				],
			);

			throw new DevicesExceptions\Terminate(
				'Websockets api client could not be started',
				$ex->getCode(),
				$ex,
			);
		}

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			function (): void {
				$this->registerLoopHandler();
			},
		);

		$this->writer->connect($this->connector, $this);
	}

	public function disconnect(): void
	{
		try {
			$this->coapClient?->disconnect();
		} catch (Throwable $ex) {
			$this->logger->error(
				'CoAP client could not be disconnected',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'local-client',
					'group' => 'client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
				],
			);
		}

		try {
			$this->wsClient?->disconnect();
		} catch (Throwable $ex) {
			$this->logger->error(
				'Websockets client could not be disconnected',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'local-client',
					'group' => 'client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
				],
			);
		}

		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}

		$this->writer->disconnect($this->connector, $this);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeChannelProperty(
		Entities\ShellyDevice $device,
		DevicesEntities\Channels\Channel $channel,
		DevicesEntities\Channels\Properties\Dynamic $property,
	): Promise\PromiseInterface
	{
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

		$valueToWrite = $this->transformer->transformValueToDevice(
			$property->getDataType(),
			$property->getFormat(),
			$state->getExpectedValue(),
		);

		if ($valueToWrite === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Property expected value could not be transformed to device'),
			);
		}

		if ($state->isPending() === true) {
			if (
				$this->wsClient !== null
				&& $device->getGeneration()->equalsValue(Types\DeviceGeneration::GENERATION_2)
			) {
				$result = $this->wsClient->writeChannelProperty($device, $property, $valueToWrite);
				assert($result instanceof Promise\ExtendedPromiseInterface);
			} elseif (
				$this->httpClient !== null
				&& $device->getGeneration()->equalsValue(Types\DeviceGeneration::GENERATION_1)
			) {
				$result = $this->httpClient->writeChannelProperty($device, $channel, $property, $valueToWrite);
				assert($result instanceof Promise\ExtendedPromiseInterface);
			} else {
				return Promise\reject(new Exceptions\InvalidArgument('Provided property state is in invalid state'));
			}

			return $result;
		}

		return Promise\reject(new Exceptions\InvalidArgument('Provided property state is in invalid state'));
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	private function handleCommunication(): void
	{
		foreach ($this->connector->getDevices() as $device) {
			assert($device instanceof Entities\ShellyDevice);

			if (
				!in_array($device->getPlainId(), $this->processedDevices, true)
				&& !$this->deviceConnectionManager->getState($device)->equalsValue(
					MetadataTypes\ConnectionState::STATE_STOPPED,
				)
			) {
				$this->processedDevices[] = $device->getPlainId();

				if ($this->readDeviceStatus($device)) {
					$this->registerLoopHandler();

					return;
				}
			}
		}

		$this->processedDevices = [];

		$this->registerLoopHandler();
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	private function readDeviceStatus(Entities\ShellyDevice $device): bool
	{
		if (!array_key_exists($device->getIdentifier(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getIdentifier()] = [];
		}

		if (array_key_exists(self::CMD_STATUS, $this->processedDevicesCommands[$device->getIdentifier()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getIdentifier()][self::CMD_STATUS];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp() < $device->getStatusReadingDelay()
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$device->getIdentifier()][self::CMD_STATUS] = $this->dateTimeFactory->getNow();

		if (
			$this->wsClient !== null
			&& $device->getGeneration()->equalsValue(Types\DeviceGeneration::GENERATION_2)
		) {
			$this->wsClient->readDeviceStates(
				$device,
				function () use ($device): void {
					$this->processedDevicesCommands[$device->getIdentifier()][self::CMD_STATUS] = $this->dateTimeFactory->getNow();
				},
				function (Throwable $ex) use ($device): void {
					$this->logger->error(
						'Could not read device status',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
							'type' => 'local-client',
							'group' => 'client',
							'exception' => [
								'message' => $ex->getMessage(),
								'code' => $ex->getCode(),
							],
							'device' => [
								'id' => $device->getPlainId(),
							],
						],
					);
				},
			);

		} elseif (
			$this->httpClient !== null
			&& $device->getGeneration()->equalsValue(Types\DeviceGeneration::GENERATION_1)
		) {
			$this->httpClient->readDeviceStates(
				$device,
				function () use ($device): void {
					$this->processedDevicesCommands[$device->getIdentifier()][self::CMD_STATUS] = $this->dateTimeFactory->getNow();
				},
				function (Throwable $ex) use ($device): void {
					$this->logger->error(
						'Could not read device status',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
							'type' => 'local-client',
							'group' => 'client',
							'exception' => [
								'message' => $ex->getMessage(),
								'code' => $ex->getCode(),
							],
							'device' => [
								'id' => $device->getPlainId(),
							],
						],
					);
				},
			);

		} else {
			return true;
		}

		return false;
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
