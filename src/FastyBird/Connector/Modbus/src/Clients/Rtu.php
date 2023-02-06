<?php declare(strict_types = 1);

/**
 * Rtu.php
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
use Nette;
use Nette\Utils;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use function array_key_exists;
use function array_merge;
use function assert;
use function get_loaded_extensions;
use function in_array;
use function is_bool;
use function is_int;
use function is_numeric;
use function is_object;
use function range;
use function sprintf;
use function strval;

/**
 * Modbus RTU devices client interface
 *
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Rtu implements Client
{

	use TReading;
	use Nette\SmartObject;

	private const READ_MAX_ATTEMPTS = 5;

	private const LOST_DELAY = 5.0; // in s - Waiting delay before another communication with device after device was lost

	private const HANDLER_START_DELAY = 2.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private bool $closed = true;

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, DateTimeInterface|int> */
	private array $processedReadRegister = [];

	/** @var array<string, DateTimeInterface> */
	private array $lostDevices = [];

	private EventLoop\TimerInterface|null $handlerTimer;

	private API\Interfaces\Serial|null $interface = null;

	private API\Rtu|null $rtu = null;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Entities\ModbusConnector $connector,
		private readonly API\RtuFactory $rtuFactory,
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

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function connect(): void
	{
		$configuration = new API\Interfaces\Configuration(
			$this->connector->getBaudRate(),
			$this->connector->getByteSize(),
			$this->connector->getStopBits(),
			$this->connector->getParity(),
			false,
			false,
		);

		$useDio = false;

		foreach (get_loaded_extensions() as $extension) {
			if (Utils\Strings::contains('dio', Utils\Strings::lower($extension))) {
				$useDio = true;

				break;
			}
		}

		$this->interface = $useDio
			? new API\Interfaces\SerialDio($this->connector->getRtuInterface(), $configuration)
			: new API\Interfaces\SerialFile($this->connector->getRtuInterface(), $configuration);

		$this->interface->open();

		$this->rtu = $this->rtuFactory->create($this->interface);

		$this->closed = false;

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			function (): void {
				$this->registerLoopHandler();
			},
		);

		$this->writer->connect($this->connector, $this);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function disconnect(): void
	{
		$this->closed = true;

		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);
		}

		$this->interface?->close();

		$this->writer->disconnect($this->connector, $this);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\NotReachable
	 * @throws Exceptions\NotSupported
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

		$station = $device->getAddress();

		if (!is_numeric($station)) {
			$this->consumer->append(new Entities\Messages\DeviceState(
				$this->connector->getId(),
				$device->getIdentifier(),
				MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_STOPPED),
			));

			return Promise\reject(new Exceptions\InvalidState('Device address is not configured'));
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

			try {
				if ($valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
					if (in_array($valueToWrite->getValue(), [0, 1], true) || is_bool($valueToWrite->getValue())) {
						$this->rtu?->writeSingleCoil(
							$station,
							$address,
							is_bool(
								$valueToWrite->getValue(),
							) ? $valueToWrite->getValue() : $valueToWrite->getValue() === 1,
						);

					} else {
						return Promise\reject(
							new Exceptions\InvalidArgument(
								'Value for boolean property have to be 1/0 or true/false',
							),
						);
					}
				} elseif (
					$valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_SHORT)
					|| $valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_USHORT)
					|| $valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_CHAR)
					|| $valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_UCHAR)
					|| $valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_INT)
					|| $valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_UINT)
					|| $valueToWrite->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_FLOAT)
				) {
					$this->rtu?->writeSingleHolding(
						$station,
						$address,
						(int) $valueToWrite->getValue(),
						$valueToWrite->getDataType(),
						$device->getByteOrder(),
					);
				} else {
					return Promise\reject(
						new Exceptions\InvalidState(sprintf(
							'Unsupported value data type: %s',
							strval($valueToWrite->getDataType()->getValue()),
						)),
					);
				}
			} catch (Exceptions\ModbusRtu $ex) {
				return Promise\reject($ex);
			}

			// Register writing failed
			return Promise\resolve();
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
				$deviceAddress = $device->getAddress();

				if (!is_int($deviceAddress)) {
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
								'type' => 'rtu-client',
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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function processDevice(Entities\ModbusDevice $device): bool
	{
		$station = $device->getAddress();
		assert(is_numeric($station));

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
						'type' => 'rtu-client',
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

		$now = $this->dateTimeFactory->getNow();

		foreach ($requests as $request) {
			foreach (range(
				$request->getStartAddress(),
				$request->getStartAddress() + $request->getQuantity(),
			) as $address) {
				if ($request instanceof Entities\Clients\ReadCoilsRequest) {
					$channel = $device->findChannelByType(
						$address,
						Types\ChannelType::get(Types\ChannelType::COIL),
					);
				} elseif ($request instanceof Entities\Clients\ReadDiscreteInputsRequest) {
					$channel = $device->findChannelByType(
						$address,
						Types\ChannelType::get(Types\ChannelType::DISCRETE_INPUT),
					);
				} elseif ($request instanceof Entities\Clients\ReadHoldingsRequest) {
					$channel = $device->findChannelByType(
						$address,
						Types\ChannelType::get(Types\ChannelType::HOLDING),
					);
				} elseif ($request instanceof Entities\Clients\ReadInputsRequest) {
					$channel = $device->findChannelByType(
						$address,
						Types\ChannelType::get(Types\ChannelType::INPUT),
					);
				} else {
					continue;
				}

				if ($channel !== null) {
					$this->processedReadRegister[$channel->getIdentifier()] = $now;
				}
			}

			try {
				if ($request instanceof Entities\Clients\ReadCoilsRequest) {
					$response = $this->rtu?->readCoils(
						$station,
						$request->getStartAddress(),
						$request->getQuantity(),
					);
				} elseif ($request instanceof Entities\Clients\ReadDiscreteInputsRequest) {
					$response = $this->rtu?->readDiscreteInputs(
						$station,
						$request->getStartAddress(),
						$request->getQuantity(),
					);
				} elseif ($request instanceof Entities\Clients\ReadHoldingsRequest) {
					$response = $this->rtu?->readHoldingRegisters(
						$station,
						$request->getStartAddress(),
						$request->getQuantity(),
						$request->getDataType(),
						$device->getByteOrder(),
					);
				} elseif ($request instanceof Entities\Clients\ReadInputsRequest) {
					$response = $this->rtu?->readHoldingRegisters(
						$station,
						$request->getStartAddress(),
						$request->getQuantity(),
						$request->getDataType(),
						$device->getByteOrder(),
					);
				} else {
					continue;
				}

				if (is_object($response)) {
					$now = $this->dateTimeFactory->getNow();

					foreach ($response->getRegisters() as $address => $value) {
						if ($request instanceof Entities\Clients\ReadCoilsRequest) {
							$channel = $device->findChannelByType(
								$address,
								Types\ChannelType::get(Types\ChannelType::COIL),
							);
						} elseif ($request instanceof Entities\Clients\ReadDiscreteInputsRequest) {
							$channel = $device->findChannelByType(
								$address,
								Types\ChannelType::get(Types\ChannelType::DISCRETE_INPUT),
							);
						} elseif ($request instanceof Entities\Clients\ReadHoldingsRequest) {
							$channel = $device->findChannelByType(
								$address,
								Types\ChannelType::get(Types\ChannelType::HOLDING),
							);
						} else {
							$channel = $device->findChannelByType(
								$address,
								Types\ChannelType::get(Types\ChannelType::INPUT),
							);
						}

						if ($channel !== null) {
							$this->processedReadRegister[$channel->getIdentifier()] = $now;

							$property = $channel->findProperty(Types\ChannelPropertyIdentifier::IDENTIFIER_VALUE);

							if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
								$this->propertyStateHelper->setValue(
									$property,
									Utils\ArrayHash::from([
										DevicesStates\Property::ACTUAL_VALUE_KEY => DevicesUtilities\ValueHelper::flattenValue(
											$this->transformer->transformValueFromDevice(
												$property->getDataType(),
												$property->getFormat(),
												$value,
												$property->getNumberOfDecimals(),
											),
										),
										DevicesStates\Property::VALID_KEY => true,
									]),
								);
							}
						}
					}
				}
			} catch (Exceptions\ModbusRtu $ex) {
				foreach (range(
					$request->getStartAddress(),
					$request->getStartAddress() + $request->getQuantity(),
				) as $address) {
					if ($request instanceof Entities\Clients\ReadCoilsRequest) {
						$channel = $device->findChannelByType(
							$address,
							Types\ChannelType::get(Types\ChannelType::COIL),
						);
					} elseif ($request instanceof Entities\Clients\ReadDiscreteInputsRequest) {
						$channel = $device->findChannelByType(
							$address,
							Types\ChannelType::get(Types\ChannelType::DISCRETE_INPUT),
						);
					} elseif ($request instanceof Entities\Clients\ReadHoldingsRequest) {
						$channel = $device->findChannelByType(
							$address,
							Types\ChannelType::get(Types\ChannelType::HOLDING),
						);
					} else {
						$channel = $device->findChannelByType(
							$address,
							Types\ChannelType::get(Types\ChannelType::INPUT),
						);
					}

					if ($channel !== null) {
						$property = $channel->findProperty(Types\ChannelPropertyIdentifier::IDENTIFIER_VALUE);

						if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
							$this->propertyStateHelper->setValue(
								$property,
								Utils\ArrayHash::from([
									DevicesStates\Property::VALID_KEY => false,
								]),
							);
						}

						// Increment failed attempts counter
						if (!array_key_exists($channel->getIdentifier(), $this->processedReadRegister)) {
							$this->processedReadRegister[$channel->getIdentifier()] = 1;
						} else {
							$this->processedReadRegister[$channel->getIdentifier()] = is_int(
								$this->processedReadRegister[$channel->getIdentifier()],
							)
								? $this->processedReadRegister[$channel->getIdentifier()] + 1
								: 1;
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
									'id' => $channel->getPlainId(),
								],
							],
						);
					}
				}

				// Something wrong during communication
				return true;
			}
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
			isset($this->processedReadRegister[$channel->getIdentifier()])
			&& is_int($this->processedReadRegister[$channel->getIdentifier()])
			&& $this->processedReadRegister[$channel->getIdentifier()] >= self::READ_MAX_ATTEMPTS
		) {
			unset($this->processedReadRegister[$channel->getIdentifier()]);

			$this->lostDevices[$device->getPlainId()] = $now;

			$this->propertyStateHelper->setValue(
				$property,
				Utils\ArrayHash::from([
					DevicesStates\Property::VALID_KEY => false,
				]),
			);

			$this->consumer->append(new Entities\Messages\DeviceState(
				$this->connector->getId(),
				$device->getIdentifier(),
				MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_LOST),
			));

			$this->logger->warning(
				'Maximum channel property read attempts reached',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'rtu-client',
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
					'property' => [
						'id' => $property->getPlainId(),
					],
				],
			);

			return null;
		}

		if (
			array_key_exists($channel->getIdentifier(), $this->processedReadRegister)
			&& $this->processedReadRegister[$channel->getIdentifier()] instanceof DateTimeInterface
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
				'type' => 'rtu-client',
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
