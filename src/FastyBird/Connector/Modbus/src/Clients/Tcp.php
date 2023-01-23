<?php declare(strict_types = 1);

/**
 * Tcp.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           31.07.22
 */

namespace FastyBird\Connector\Modbus\Clients;

use DateTimeInterface;
use Exception;
use FastyBird\Connector\Modbus\API;
use FastyBird\Connector\Modbus\Consumers;
use FastyBird\Connector\Modbus\Entities;
use FastyBird\Connector\Modbus\Exceptions;
use FastyBird\Connector\Modbus\Helpers;
use FastyBird\Connector\Modbus\Writers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use ModbusTcpClient\Composer as ModbusComposer;
use ModbusTcpClient\Packet as ModbusPacket;
use ModbusTcpClient\Utils as ModbusUtils;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use React\Socket;
use Throwable;
use function array_key_exists;
use function array_merge;
use function assert;
use function in_array;
use function is_int;
use function is_string;
use function microtime;
use function print_r;
use const PHP_EOL;

/**
 * Modbus TCP devices client interface
 *
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Tcp implements Client
{

	use Nette\SmartObject;

	private const READ_MAX_ATTEMPTS = 5;

	private const LOST_DELAY = 5.0; // in s - Waiting delay before another communication with device after device was lost

	private const HANDLER_START_DELAY = 2.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private bool $closed = true;

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, DateTimeInterface|int> */
	private array $processedReadProperties = [];

	/** @var array<string, array<string, ModbusComposer\Read\Coil\ReadCoilAddress>> */
	private array $readCoilsStatusesAddresses = [];

	/** @var array<string, array<string, ModbusComposer\Read\Coil\ReadCoilAddress>> */
	private array $readInputsStatusesAddresses = [];

	/** @var array<string, array<string, ModbusComposer\Read\Register\ReadRegisterAddress>> */
	private array $readHoldingRegistersAddresses = [];

	/** @var array<string, array<string, ModbusComposer\Read\Register\ReadRegisterAddress>> */
	private array $readInputsRegistersAddresses = [];

	/** @var array<string, DateTimeInterface> */
	private array $lostDevices = [];

	private EventLoop\TimerInterface|null $handlerTimer;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Entities\ModbusConnector $connector,
		private readonly Helpers\Property $propertyStateHelper,
		private readonly API\Transformer $transformer,
		private readonly Consumers\Messages $consumer,
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

	public function connect(): void
	{
		$this->closed = false;

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
		$this->closed = true;

		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);
		}

		$this->writer->disconnect($this->connector, $this);
	}

	public function writeChannelProperty(
		Entities\ModbusDevice $device,
		Entities\ModbusChannel $channel,
		DevicesEntities\Channels\Properties\Dynamic $property,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		return $deferred->promise();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function handleCommunication(): void
	{
		foreach ($this->processedReadProperties as $index => $processedProperty) {
			if (
				$processedProperty instanceof DateTimeInterface
				&& ((float) $this->dateTimeFactory->getNow()->format('Uv') - (float) $processedProperty->format(
					'Uv',
				)) >= self::READ_DEBOUNCE_DELAY
			) {
				unset($this->processedReadProperties[$index]);
			}
		}

		foreach ($this->connector->getDevices() as $device) {
			assert($device instanceof Entities\ModbusDevice);

			if (
				!in_array($device->getPlainId(), $this->processedDevices, true)
				&& !$this->deviceConnectionManager->getState($device)
					->equalsValue(MetadataTypes\ConnectionState::STATE_STOPPED)
			) {
				$deviceAddress = $device->getIpAddress();

				if (!is_string($deviceAddress)) {
					$this->consumer->append(new Entities\Messages\DeviceState(
						$this->connector->getId(),
						$device->getIdentifier(),
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_STOPPED),
					));

					continue;
				}

				// Check if device is lost or not
				if (array_key_exists($device->getPlainId(), $this->lostDevices)) {
					if (
						!$this->deviceConnectionManager->getState($device)
							->equalsValue(MetadataTypes\ConnectionState::STATE_LOST)
					) {
						$this->logger->debug(
							'Device is lost',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
								'type' => 'tcp-client',
								'group' => 'client',
								'connector' => [
									'id' => $this->connector->getPlainId(),
								],
								'device' => [
									'id' => $device->getPlainId(),
								],
							],
						);

						$this->consumer->append(new Entities\Messages\DeviceState(
							$this->connector->getId(),
							$device->getIdentifier(),
							MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_LOST),
						));
					}

					if (
						$this->dateTimeFactory->getNow()->getTimestamp() - $this->lostDevices[$device->getId()
							->toString()]->getTimestamp() < self::LOST_DELAY
					) {
						continue;
					}
				}

				// Check device state...
				if (
					!$this->deviceConnectionManager->getState($device)
						->equalsValue(Metadata\Types\ConnectionState::STATE_CONNECTED)
				) {
					// ... and if it is not ready, set it to ready
					$this->consumer->append(new Entities\Messages\DeviceState(
						$this->connector->getId(),
						$device->getIdentifier(),
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_CONNECTED),
					));
				}

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
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function processDevice(Entities\ModbusDevice $device): bool
	{
		$now = $this->dateTimeFactory->getNow();

		$ipAddress = $device->getIpAddress();
		assert(is_string($ipAddress));

		$this->readCoilsStatusesAddresses = $this->readInputsStatusesAddresses = [];
		$this->readHoldingRegistersAddresses = $this->readInputsRegistersAddresses = [];

		foreach ($device->getChannels() as $channel) {
			$address = $channel->getAddress();

			if (!is_int($address)) {
				foreach ($channel->getProperties() as $property) {
					if (!$property instanceof DevicesEntities\Channels\Properties\Dynamic) {
						continue;
					}

					$this->propertyStateHelper->setValue(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::VALID_KEY => false,
							DevicesStates\Property::EXPECTED_VALUE_KEY => null,
							DevicesStates\Property::PENDING_KEY => false,
						]),
					);
				}

				$this->logger->warning(
					'Channel address is missing',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
						'type' => 'tcp-client',
						'group' => 'client',
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
						'device' => [
							'id' => $device->getPlainId(),
						],
						'channel' => [
							'id' => $channel->getPlainId(),
						],
					],
				);

				continue;
			}

			foreach ($channel->getProperties() as $property) {
				if (!$property instanceof DevicesEntities\Channels\Properties\Dynamic) {
					continue;
				}

				/**
				 * Channel property reading
				 */

				$this->createReadAddress($ipAddress, $address, $device, $property);
			}
		}

		if (
			$this->readCoilsStatusesAddresses === []
			&& $this->readInputsStatusesAddresses === []
			&& $this->readHoldingRegistersAddresses === []
			&& $this->readInputsRegistersAddresses === []
		) {
			return false;
		}

		$requests = [];

		if ($this->readCoilsStatusesAddresses !== []) {
			$addressSplitter = new ModbusComposer\Read\Coil\ReadCoilAddressSplitter(
				ModbusPacket\ModbusFunction\ReadCoilsRequest::class,
			);

			$requests = array_merge($requests, $addressSplitter->split($this->readCoilsStatusesAddresses));
		}

		if ($this->readInputsStatusesAddresses !== []) {
			$addressSplitter = new ModbusComposer\Read\Coil\ReadCoilAddressSplitter(
				ModbusPacket\ModbusFunction\ReadInputDiscretesRequest::class,
			);

			$requests = array_merge($requests, $addressSplitter->split($this->readInputsStatusesAddresses));
		}

		if ($this->readHoldingRegistersAddresses !== []) {
			$addressSplitter = new ModbusComposer\Read\Register\ReadRegisterAddressSplitter(
				ModbusPacket\ModbusFunction\ReadHoldingRegistersRequest::class,
			);

			$requests = array_merge($requests, $addressSplitter->split($this->readHoldingRegistersAddresses));
		}

		if ($this->readInputsRegistersAddresses !== []) {
			$addressSplitter = new ModbusComposer\Read\Register\ReadRegisterAddressSplitter(
				ModbusPacket\ModbusFunction\ReadInputRegistersRequest::class,
			);

			$requests = array_merge($requests, $addressSplitter->split($this->readInputsRegistersAddresses));
		}

		if ($requests === []) {
			return false;
		}

		foreach ($requests as $request) {
			$connector = new Socket\Connector($this->eventLoop, [
				'dns' => false,
				'timeout' => 0.2,
			]);

			$connector->connect($request->getUri())
				->then(function (Socket\ConnectionInterface $connection) use ($request, $device): void {
					$receivedData = '';

					$this->logger->debug(
						'Connected to device for registers reading',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
							'type' => 'tcp-client',
							'group' => 'client',
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
							'device' => [
								'id' => $device->getPlainId(),
							],
							'connection' => [
								'uri' => $request->getUri(),
							],
						],
					);

					$connection->write($request);

					// Wait for response event
					$connection->on(
						'data',
						function ($data) use ($connection, $request, $device, &$receivedData): void {
							// There are rare cases when MODBUS packet is received by multiple fragmented TCP packets and it could
							// take PHP multiple reads from stream to get full packet. So we concatenate data and check if all that
							// we have received makes a complete modbus packet.
							$receivedData .= $data;

							if (ModbusUtils\Packet::isCompleteLength($receivedData)) {
								echo microtime(true) . ": uri: {$request->getUri()}, complete response: " . print_r(
									$request->parse($receivedData),
									true,
								) . PHP_EOL;

								$connection->end();
							} else {
								$this->logger->debug(
									'Received partial response',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
										'type' => 'tcp-client',
										'group' => 'client',
										'connector' => [
											'id' => $this->connector->getPlainId(),
										],
										'device' => [
											'id' => $device->getPlainId(),
										],
										'connection' => [
											'uri' => $request->getUri(),
										],
									],
								);
							}
						},
					);

					$connection->on('error', function (Throwable $ex) use ($connection, $request, $device): void {
						$this->logger->error(
							'Could not handle register reading',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
								'type' => 'tcp-client',
								'group' => 'client',
								'exception' => [
									'message' => $ex->getMessage(),
									'code' => $ex->getCode(),
								],
								'connector' => [
									'id' => $this->connector->getPlainId(),
								],
								'device' => [
									'id' => $device->getPlainId(),
								],
								'connection' => [
									'uri' => $request->getUri(),
								],
							],
						);

						$connection->end();
					});
				})
				->otherwise(function (Throwable $ex) use ($request, $device, $now): void {
					$this->lostDevices[$device->getPlainId()] = $now;

					$this->logger->debug(
						'Device is lost',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
							'type' => 'tcp-client',
							'group' => 'client',
							'exception' => [
								'message' => $ex->getMessage(),
								'code' => $ex->getCode(),
							],
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
							'device' => [
								'id' => $device->getPlainId(),
							],
							'connection' => [
								'uri' => $request->getUri(),
							],
						],
					);

					$this->consumer->append(new Entities\Messages\DeviceState(
						$this->connector->getId(),
						$device->getIdentifier(),
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_LOST),
					));
				});
		}

		return true;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function createReadAddress(
		string $ipAddress,
		int $address,
		Entities\ModbusDevice $device,
		DevicesEntities\Channels\Properties\Dynamic $property,
	): void
	{
		$unitIdPrefix = ModbusComposer\AddressSplitter::UNIT_ID_PREFIX;
		$modbusPath = "{$ipAddress}{$unitIdPrefix}{$device->getUnitId()}";

		if (
			// Property have to be readable
			$property->isQueryable()
		) {
			$deviceExpectedDataType = $this->transformer->determineDeviceReadDataType(
				$property->getDataType(),
				$property->getFormat(),
			);

			if ($deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
				$address = new ModbusComposer\Read\Coil\ReadCoilAddress(
					$address,
					$property->getIdentifier(),
				);

				if ($property->isSettable()) {
					$this->readCoilsStatusesAddresses[$modbusPath][$address->getName()] = $address;
				} else {
					$this->readInputsStatusesAddresses[$modbusPath][$address->getName()] = $address;
				}

				return;
			} elseif (
				$deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_CHAR)
				|| $deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_UCHAR)
				|| $deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SHORT)
				|| $deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_USHORT)
				|| $deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_INT)
				|| $deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_UINT)
				|| $deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_FLOAT)
				|| $deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_STRING)
			) {
				if (
					$deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_CHAR)
					|| $deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_UCHAR)
				) {
					$address = new ModbusComposer\Read\Register\ByteReadRegisterAddress(
						$address,
						true,
						$property->getIdentifier(),
					);

				} elseif ($deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SHORT)) {
					$address = new ModbusComposer\Read\Register\ReadRegisterAddress(
						$address,
						ModbusComposer\Address::TYPE_INT16,
						$property->getIdentifier(),
					);

				} elseif ($deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_USHORT)) {
					$address = new ModbusComposer\Read\Register\ReadRegisterAddress(
						$address,
						ModbusComposer\Address::TYPE_UINT16,
						$property->getIdentifier(),
					);

				} elseif ($deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_INT)) {
					$address = new ModbusComposer\Read\Register\ReadRegisterAddress(
						$address,
						ModbusComposer\Address::TYPE_INT32,
						$property->getIdentifier(),
					);

				} elseif ($deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_UINT)) {
					$address = new ModbusComposer\Read\Register\ReadRegisterAddress(
						$address,
						ModbusComposer\Address::TYPE_UINT32,
						$property->getIdentifier(),
					);

				} elseif ($deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_FLOAT)) {
					$address = new ModbusComposer\Read\Register\ReadRegisterAddress(
						$address,
						ModbusComposer\Address::TYPE_FLOAT,
						$property->getIdentifier(),
					);

				} elseif ($deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_STRING)) {
					$address = new ModbusComposer\Read\Register\ReadRegisterAddress(
						$address,
						ModbusComposer\Address::TYPE_STRING,
						$property->getIdentifier(),
					);

				} else {
					return;
				}

				if ($property->isSettable()) {
					$this->readHoldingRegistersAddresses[$modbusPath][$address->getName()] = $address;
				} else {
					$this->readInputsRegistersAddresses[$modbusPath][$address->getName()] = $address;
				}
			}
		}

		$this->logger->warning(
			'Channel property data type is not supported for now',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
				'type' => 'tcp-client',
				'group' => 'client',
				'connector' => [
					'id' => $this->connector->getPlainId(),
				],
				'device' => [
					'id' => $device->getPlainId(),
				],
				'channel' => [
					'id' => $property->getChannel()->getPlainId(),
				],
				'property' => [
					'id' => $property->getPlainId(),
				],
			],
		);
	}

	private function registerLoopHandler(): void
	{
		$this->handlerTimer = $this->eventLoop->addTimer(
			self::HANDLER_PROCESSING_INTERVAL,
			function (): void {
				if ($this->closed) {
					return;
				}

				$this->handleCommunication();
			},
		);
	}

}
