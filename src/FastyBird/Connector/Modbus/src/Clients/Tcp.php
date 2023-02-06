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
use FastyBird\Connector\Modbus\Types;
use FastyBird\Connector\Modbus\Writers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use InvalidArgumentException;
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
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function sprintf;
use function strval;

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

	use TReading;
	use Nette\SmartObject;

	private const LOST_DELAY = 5.0; // in s - Waiting delay before another communication with device after device was lost

	private const HANDLER_START_DELAY = 2.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private bool $closed = true;

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, DateTimeInterface> */
	private array $processedReadRegister = [];

	/** @var array<string, DateTimeInterface> */
	private array $lostDevices = [];

	private EventLoop\TimerInterface|null $handlerTimer;

	private API\Tcp|null $tcp = null;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Entities\ModbusConnector $connector,
		private readonly API\TcpFactory $tcpFactory,
		private readonly API\Transformer $transformer,
		private readonly Helpers\Property $propertyStateHelper,
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
		$this->tcp = $this->tcpFactory->create();

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

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeChannelProperty(
		Entities\ModbusDevice $device,
		Entities\ModbusChannel $channel,
		DevicesEntities\Channels\Properties\Dynamic $property,
	): Promise\PromiseInterface
	{
		$state = $this->channelPropertiesStates->getValue($property);

		$ipAddress = $device->getIpAddress();

		if ($ipAddress === null) {
			$this->consumer->append(new Entities\Messages\DeviceState(
				$this->connector->getId(),
				$device->getIdentifier(),
				MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_STOPPED),
			));

			return Promise\reject(new Exceptions\InvalidState('Device ip address is not configured'));
		}

		$address = $channel->getAddress();

		if (!is_int($address)) {
			return Promise\reject(new Exceptions\InvalidState('Channel address is not configured'));
		}

		if (
			$state?->getExpectedValue() !== null
			&& $state->isPending() === true
		) {
			$deviceExpectedDataType = $this->transformer->determineDeviceWriteDataType(
				$property->getDataType(),
				$property->getFormat(),
			);

			if (!in_array($deviceExpectedDataType->getValue(), [
				MetadataTypes\DataType::DATA_TYPE_CHAR,
				MetadataTypes\DataType::DATA_TYPE_UCHAR,
				MetadataTypes\DataType::DATA_TYPE_SHORT,
				MetadataTypes\DataType::DATA_TYPE_USHORT,
				MetadataTypes\DataType::DATA_TYPE_INT,
				MetadataTypes\DataType::DATA_TYPE_UINT,
				MetadataTypes\DataType::DATA_TYPE_FLOAT,
				MetadataTypes\DataType::DATA_TYPE_BOOLEAN,
				MetadataTypes\DataType::DATA_TYPE_STRING,
			], true)) {
				return Promise\reject(
					new Exceptions\NotSupported(
						sprintf(
							'Trying to write property with unsupported data type: %s for channel property',
							strval($deviceExpectedDataType->getValue()),
						),
					),
				);
			}

			$valueToWrite = $this->transformer->transformValueToDevice(
				$property->getDataType(),
				$property->getFormat(),
				$state->getExpectedValue(),
				$property->getNumberOfDecimals(),
			);

			if ($valueToWrite === null) {
				return Promise\reject(new Exceptions\InvalidState('Value to write to register is invalid'));
			}

			$deferred = new Promise\Deferred();

			$unitIdPrefix = ModbusComposer\AddressSplitter::UNIT_ID_PREFIX;
			$modbusPath = "{$ipAddress}{$unitIdPrefix}{$device->getUnitId()}";

			if ($valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
				if (!is_bool($valueToWrite->getValue())) {
					return Promise\reject(new Exceptions\InvalidState('Value to write to register is invalid'));
				}

				$address = new ModbusComposer\Write\Coil\WriteCoilAddress(
					$address,
					$valueToWrite->getValue(),
				);

				$writeAddresses = [
					$modbusPath => [
						strval($address->getAddress()) => $address,
					],
				];

				$addressSplitter = new ModbusComposer\Write\Coil\WriteCoilAddressSplitter(
					ModbusPacket\ModbusFunction\WriteMultipleCoilsRequest::class,
				);

				// @phpstan-ignore-next-line
				$requests = $addressSplitter->split($writeAddresses);
			} elseif (
				$valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_SHORT)
				|| $valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_USHORT)
				|| $valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_INT)
				|| $valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_UINT)
				|| $valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_FLOAT)
				|| $valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_STRING)
			) {
				if (
					$valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_SHORT)
					&& is_int($valueToWrite->getValue())
				) {
					$address = new ModbusComposer\Write\Register\WriteRegisterAddress(
						$address,
						ModbusComposer\Address::TYPE_INT16,
						$valueToWrite->getValue(),
					);
				} elseif (
					$valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_USHORT)
					&& is_int($valueToWrite->getValue())
				) {
					$address = new ModbusComposer\Write\Register\WriteRegisterAddress(
						$address,
						ModbusComposer\Address::TYPE_UINT16,
						$valueToWrite->getValue(),
					);
				} elseif (
					$valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_INT)
					&& is_int($valueToWrite->getValue())
				) {
					$address = new ModbusComposer\Write\Register\WriteRegisterAddress(
						$address,
						ModbusComposer\Address::TYPE_INT32,
						$valueToWrite->getValue(),
					);
				} elseif (
					$valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_INT)
					&& is_int($valueToWrite->getValue())
				) {
					$address = new ModbusComposer\Write\Register\WriteRegisterAddress(
						$address,
						ModbusComposer\Address::TYPE_UINT32,
						$valueToWrite->getValue(),
					);
				} elseif (
					$valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_FLOAT)
					&& is_float($valueToWrite->getValue())
				) {
					$address = new ModbusComposer\Write\Register\WriteRegisterAddress(
						$address,
						ModbusComposer\Address::TYPE_FLOAT,
						$valueToWrite->getValue(),
					);
				} elseif (
					$valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_STRING)
					&& is_string($valueToWrite->getValue())
				) {
					$address = new ModbusComposer\Write\Register\WriteRegisterAddress(
						$address,
						ModbusComposer\Address::TYPE_STRING,
						$valueToWrite->getValue(),
					);
				} else {
					return Promise\reject(new Exceptions\InvalidState('Value to write to register is invalid'));
				}

				$writeAddresses = [
					$modbusPath => [
						strval($address->getAddress()) => $address,
					],
				];

				$addressSplitter = new ModbusComposer\Write\Register\WriteRegisterAddressSplitter(
					ModbusPacket\ModbusFunction\WriteMultipleRegistersRequest::class,
				);

				// @phpstan-ignore-next-line
				$requests = $addressSplitter->split($writeAddresses);
			} else {
				return Promise\reject(
					new Exceptions\NotSupported(
						sprintf(
							'Trying to write property with unsupported data type: %s for channel property',
							strval($deviceExpectedDataType->getValue()),
						),
					),
				);
			}

			foreach ($requests as $request) {
				assert(
					$request instanceof ModbusComposer\Write\Coil\WriteCoilRequest
					|| $request instanceof ModbusComposer\Write\Register\WriteRegisterRequest,
				);

				$connector = new Socket\Connector($this->eventLoop, [
					'dns' => false,
					'timeout' => 0.2,
				]);

				$connector->connect($request->getUri())
					->then(function (Socket\ConnectionInterface $connection) use ($request, $device, $deferred): void {
						$receivedData = '';

						$this->logger->debug(
							'Connected to device for registers writing',
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
							function ($data) use ($connection, $request, $device, $deferred, &$receivedData): void {
								$receivedData .= $data;

								if (ModbusUtils\Packet::isCompleteLength($receivedData)) {
									$response = $request->parse($receivedData);

									if ($response instanceof ModbusPacket\ErrorResponse) {
										$deferred->reject();
									} elseif (
										$response instanceof ModbusPacket\ModbusFunction\WriteSingleCoilResponse
										|| $response instanceof ModbusPacket\ModbusFunction\WriteSingleRegisterResponse
									) {
										$deferred->resolve();
									}

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

						$connection->on('error', static function (Throwable $ex) use ($connection, $deferred): void {
							$deferred->reject($ex);

							$connection->end();
						});
					})
					->otherwise(static function (Throwable $ex) use ($deferred): void {
						$deferred->reject($ex);
					});
			}

			return $deferred->promise();
		}

		return Promise\reject(new Exceptions\InvalidArgument('Provided property state is in invalid state'));
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function handleCommunication(): void
	{
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

		$port = $device->getPort();

		$deviceAddress = $ipAddress . ':' . $port;

		$unitId = $device->getUnitId();

		$coilsAddresses = $discreteInputsAddresses = $holdingAddresses = $inputsAddresses = [];

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

			$registerReadAddress = $this->createReadAddress($device, $channel);

			if ($registerReadAddress instanceof Entities\Clients\ReadCoilAddress) {
				$coilsAddresses[] = $registerReadAddress;
			} elseif ($registerReadAddress instanceof Entities\Clients\ReadDiscreteInputAddress) {
				$discreteInputsAddresses[] = $registerReadAddress;
			} elseif ($registerReadAddress instanceof Entities\Clients\ReadHoldingAddress) {
				$holdingAddresses[] = $registerReadAddress;
			} elseif ($registerReadAddress instanceof Entities\Clients\ReadInputAddress) {
				$inputsAddresses[] = $registerReadAddress;
			}
		}

		if (
			$coilsAddresses === []
			&& $discreteInputsAddresses === []
			&& $holdingAddresses === []
			&& $inputsAddresses === []
		) {
			return false;
		}

		$requests = [];

		if ($coilsAddresses !== []) {
			$requests = array_merge($requests, $this->split($coilsAddresses));
		}

		if ($discreteInputsAddresses !== []) {
			$requests = array_merge($requests, $this->split($discreteInputsAddresses));
		}

		if ($holdingAddresses !== []) {
			$requests = array_merge($requests, $this->split($holdingAddresses));
		}

		if ($inputsAddresses !== []) {
			$requests = array_merge($requests, $this->split($inputsAddresses));
		}

		if ($requests === []) {
			return false;
		}

		foreach ($requests as $request) {
			foreach ($request->getAddresses() as $address) {
				$this->processedReadRegister[$address->getChannel()->getIdentifier()] = $now;
			}

			if ($request instanceof Entities\Clients\ReadCoilsRequest) {
				$promise = $this->tcp?->readCoils(
					$deviceAddress,
					$unitId,
					$request->getStartAddress(),
					$request->getQuantity(),
				);
			} elseif ($request instanceof Entities\Clients\ReadDiscreteInputsRequest) {
				$promise = $this->tcp?->readDiscreteInputs(
					$deviceAddress,
					$unitId,
					$request->getStartAddress(),
					$request->getQuantity(),
				);
			} elseif ($request instanceof Entities\Clients\ReadHoldingsRequest) {
				$promise = $this->tcp?->readHoldingRegisters(
					$deviceAddress,
					$unitId,
					$request->getStartAddress(),
					$request->getQuantity(),
					$request->getDataType(),
					$device->getByteOrder(),
				);
			} elseif ($request instanceof Entities\Clients\ReadInputsRequest) {
				$promise = $this->tcp?->readHoldingRegisters(
					$deviceAddress,
					$unitId,
					$request->getStartAddress(),
					$request->getQuantity(),
					$request->getDataType(),
					$device->getByteOrder(),
				);
			} else {
				continue;
			}

			$promise?->then(
				function (Entities\API\Entity $response) use ($request, $now): void {
					if (
						!$response instanceof Entities\API\ReadDigitalInputs
						&& !$response instanceof Entities\API\ReadAnalogInputs
					) {
						return;
					}

					foreach ($request->getAddresses() as $address) {
						$this->processedReadRegister[$address->getChannel()->getIdentifier()] = $now;

						$property = $address->getChannel()->findProperty(
							Types\ChannelPropertyIdentifier::IDENTIFIER_VALUE,
						);

						if (
							$property instanceof DevicesEntities\Channels\Properties\Dynamic
							&& $response->findRegister($address->getAddress()) !== null
						) {
							$this->propertyStateHelper->setValue(
								$property,
								Utils\ArrayHash::from([
									DevicesStates\Property::ACTUAL_VALUE_KEY => DevicesUtilities\ValueHelper::flattenValue(
										$this->transformer->transformValueFromDevice(
											$property->getDataType(),
											$property->getFormat(),
											$response->findRegister($address->getAddress()),
											$property->getNumberOfDecimals(),
										),
									),
									DevicesStates\Property::VALID_KEY => true,
								]),
							);
						}
					}
				},
				function (Throwable $ex) use ($request, $device, $now): void {
					if ($ex instanceof Exceptions\ModbusTcp) {
						foreach ($request->getAddresses() as $address) {
							$property = $address->getChannel()->findProperty(
								Types\ChannelPropertyIdentifier::IDENTIFIER_VALUE,
							);

							if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
								$this->propertyStateHelper->setValue(
									$property,
									Utils\ArrayHash::from([
										DevicesStates\Property::VALID_KEY => false,
									]),
								);
							}

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
									'channel' => [
										'id' => $address->getChannel()->getPlainId(),
									],
								],
							);
						}
					} else {
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
							],
						);

						$this->consumer->append(new Entities\Messages\DeviceState(
							$this->connector->getId(),
							$device->getIdentifier(),
							MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_LOST),
						));
					}
				},
			);
		}

		return true;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function createReadAddress(
		Entities\ModbusDevice $device,
		Entities\ModbusChannel $channel,
	): Entities\Clients\ReadAddress|null
	{
		$now = $this->dateTimeFactory->getNow();

		$property = $channel->findProperty(Types\ChannelPropertyIdentifier::IDENTIFIER_VALUE);

		if (
			!$property instanceof DevicesEntities\Channels\Properties\Dynamic
			|| !$property->isQueryable()
		) {
			return null;
		}

		$address = $channel->getAddress();

		if ($address === null) {
			return null;
		}

		if (
			array_key_exists($channel->getIdentifier(), $this->processedReadRegister)
			&& $now->getTimestamp() - $this->processedReadRegister[$channel->getIdentifier()]->getTimestamp() < $channel->getReadingDelay()
		) {
			return null;
		}

		$deviceExpectedDataType = $this->transformer->determineDeviceReadDataType(
			$property->getDataType(),
			$property->getFormat(),
		);

		if ($deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
			return $property->isSettable()
				? new Entities\Clients\ReadCoilAddress($address, $channel)
				: new Entities\Clients\ReadDiscreteInputAddress(
					$address,
					$channel,
				);
		} elseif (
			$deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_CHAR)
			|| $deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_UCHAR)
			|| $deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SHORT)
			|| $deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_USHORT)
			|| $deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_INT)
			|| $deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_UINT)
			|| $deviceExpectedDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_FLOAT)
		) {
			return $property->isSettable()
				? new Entities\Clients\ReadHoldingAddress($address, $channel)
				: new Entities\Clients\ReadInputAddress($address, $channel);
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

		return null;
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
