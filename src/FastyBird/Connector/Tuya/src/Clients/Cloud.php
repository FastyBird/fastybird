<?php declare(strict_types = 1);

/**
 * Cloud.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           25.08.22
 */

namespace FastyBird\Connector\Tuya\Clients;

use DateTimeInterface;
use Exception;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Consumers;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Writers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use InvalidArgumentException;
use Nette;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use Throwable;
use function array_key_exists;
use function array_map;
use function assert;
use function in_array;
use function is_string;

/**
 * Cloud devices client
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Cloud implements Client
{

	use Nette\SmartObject;

	private const HANDLER_START_DELAY = 2.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const HEARTBEAT_DELAY = 600;

	private const CMD_STATUS = 'status';

	private const CMD_HEARTBEAT = 'hearbeat';

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, array<string, DateTimeInterface|bool>> */
	private array $processedDevicesCommands = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	private API\OpenApi $openApiApi;

	private API\OpenPulsar $openPulsar;

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function __construct(
		private readonly Entities\TuyaConnector $connector,
		private readonly Consumers\Messages $consumer,
		API\OpenApiFactory $openApiApiFactory,
		API\OpenPulsarFactory $openPulsarApiFactory,
		private readonly Writers\Writer $writer,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
		assert(is_string($this->connector->getAccessId()));
		assert(is_string($this->connector->getAccessSecret()));

		$this->openApiApi = $openApiApiFactory->create(
			$this->connector->getIdentifier(),
			$this->connector->getAccessId(),
			$this->connector->getAccessSecret(),
			$this->connector->getOpenApiEndpoint(),
		);

		$this->openPulsar = $openPulsarApiFactory->create(
			$this->connector->getIdentifier(),
			$this->connector->getAccessId(),
			$this->connector->getAccessSecret(),
			$this->connector->getOpenPulsarTopic(),
			$this->connector->getOpenPulsarEndpoint(),
		);

		$this->openPulsar->on('message', function (Entities\API\DeviceStatus|Entities\API\DeviceState $message): void {
			if ($message instanceof Entities\API\DeviceState) {
				$this->consumer->append(
					new Entities\Messages\DeviceState(
						$this->connector->getId(),
						$message->getIdentifier(),
						$message->getState(),
					),
				);
			} else {
				$this->consumer->append(
					new Entities\Messages\DeviceStatus(
						$this->connector->getId(),
						$message->getIdentifier(),
						array_map(
							static fn (Entities\API\DataPointStatus $dps): Entities\Messages\DataPointStatus => new Entities\Messages\DataPointStatus(
								$dps->getCode(),
								$dps->getValue(),
							),
							$message->getDataPoints(),
						),
					),
				);
			}
		});

		$this->openPulsar->on('error', static function (Throwable $ex): void {
			throw new DevicesExceptions\Terminate(
				'Tuya cloud websockets client could not be created',
				$ex->getCode(),
				$ex,
			);
		});
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 */
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

		$this->openPulsar->connect();

		$this->writer->connect($this->connector, $this);
	}

	public function disconnect(): void
	{
		$this->openPulsar->disconnect();

		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}

		$this->writer->disconnect($this->connector, $this);

		$this->openApiApi->disconnect();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeChannelProperty(
		Entities\TuyaDevice $device,
		DevicesEntities\Channels\Channel $channel,
		DevicesEntities\Channels\Properties\Dynamic|MetadataEntities\DevicesModule\ChannelDynamicProperty $property,
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

		if ($state->isPending() === true) {
			return $this->openApiApi->setDeviceStatus(
				$device->getIdentifier(),
				$property->getIdentifier(),
				$expectedValue,
			);
		}

		return Promise\reject(new Exceptions\InvalidArgument('Provided property state is in invalid state'));
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Exception
	 */
	private function handleCommunication(): void
	{
		if (!$this->openApiApi->isConnected()) {
			$this->openApiApi->connect();
		}

		$findDevicesQuery = new DevicesQueries\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		foreach ($this->devicesRepository->findAllBy($findDevicesQuery, Entities\TuyaDevice::class) as $device) {
			assert($device instanceof Entities\TuyaDevice);

			if (
				!in_array($device->getPlainId(), $this->processedDevices, true)
				&& !$this->deviceConnectionManager->getState($device)->equalsValue(
					MetadataTypes\ConnectionState::STATE_STOPPED,
				)
			) {
				$this->processedDevices[] = $device->getPlainId();

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
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Exception
	 */
	private function processDevice(Entities\TuyaDevice $device): bool
	{
		if ($this->readDeviceInformation($device)) {
			return true;
		}

		return $this->readDeviceStatus($device);
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function readDeviceInformation(Entities\TuyaDevice $device): bool
	{
		if (!array_key_exists($device->getIdentifier(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getIdentifier()] = [];
		}

		if (array_key_exists(self::CMD_HEARTBEAT, $this->processedDevicesCommands[$device->getIdentifier()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getIdentifier()][self::CMD_HEARTBEAT];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp() < self::HEARTBEAT_DELAY
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$device->getIdentifier()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();

		$this->openApiApi->getDeviceInformation($device->getIdentifier())
			->then(function (Entities\API\DeviceInformation $deviceInformation) use ($device): void {
				$this->processedDevicesCommands[$device->getIdentifier()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();

				$this->consumer->append(
					new Entities\Messages\DeviceState(
						$device->getConnector()->getId(),
						$device->getIdentifier(),
						$deviceInformation->isOnline() ? MetadataTypes\ConnectionState::get(
							MetadataTypes\ConnectionState::STATE_CONNECTED,
						) : MetadataTypes\ConnectionState::get(
							MetadataTypes\ConnectionState::STATE_DISCONNECTED,
						),
					),
				);
			})
			->otherwise(function (Throwable $ex): void {
				$this->logger->error(
					'Could not call cloud openapi',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'cloud-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
					],
				);

				throw new DevicesExceptions\Terminate(
					'Could not call cloud openapi',
					$ex->getCode(),
					$ex,
				);
			});

		return true;
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function readDeviceStatus(Entities\TuyaDevice $device): bool
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

		$this->openApiApi->getDeviceStatus($device->getIdentifier())
			->then(function (array $statuses) use ($device): void {
				$this->processedDevicesCommands[$device->getIdentifier()][self::CMD_STATUS] = $this->dateTimeFactory->getNow();

				$dataPointsStatuses = [];

				foreach ($statuses as $status) {
					if (!in_array($status->getCode(), $device->getExcludedDps(), true)) {
						$dataPointsStatuses[] = new Entities\Messages\DataPointStatus(
							$status->getCode(),
							$status->getValue(),
						);
					}
				}

				$this->consumer->append(new Entities\Messages\DeviceStatus(
					$this->connector->getId(),
					$device->getIdentifier(),
					$dataPointsStatuses,
				));
			})
			->otherwise(function (Throwable $ex): void {
				if ($ex instanceof Exceptions\OpenApiError) {
					$this->logger->warning(
						'Calling Tuya cloud failed',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
							'type' => 'cloud-client',
							'error' => $ex->getMessage(),
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
						],
					);

					return;
				}

				if (!$ex instanceof Exceptions\OpenApiCall) {
					$this->logger->error(
						'Calling Tuya cloud failed',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
							'type' => 'cloud-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
						],
					);

					throw new DevicesExceptions\Terminate(
						'Calling Tuya cloud failed',
						$ex->getCode(),
						$ex,
					);
				}
			});

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
