<?php declare(strict_types = 1);

/**
 * TReading.php
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

use FastyBird\Connector\Modbus\API;
use FastyBird\Connector\Modbus\Clients\Requests\ReadAddress;
use FastyBird\Connector\Modbus\Clients\Requests\ReadResponse;
use FastyBird\Connector\Modbus\Exceptions;
use FastyBird\Connector\Modbus\Helpers;
use FastyBird\Connector\Modbus\Queue;
use FastyBird\Connector\Modbus\Types;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use function array_splice;
use function usort;

/**
 * @property-read API\Transformer $transformer
 * @property-read Queue\MessageBuilder $messageBuilder
 * @property-read Queue\Queue $queue
 * @property-read Helpers\Device $deviceHelper
 * @property-read DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository
 */
trait TReading
{

	/**
	 * @param array<ReadAddress> $addresses
	 *
	 * @return array<ReadResponse>
	 */
	private function split(array $addresses, int $maxAddressesPerModbusRequest): array
	{
		if ($addresses === []) {
			return [];
		}

		$result = [];

		// Sort by address to help chunking
		usort(
			$addresses,
			static fn (Requests\ReadAddress $a, Requests\ReadAddress $b) => $a->getAddress() <=> $b->getAddress()
		);

		$startAddress = null;
		$previousAddress = null;
		$quantity = 0;
		$chunk = [];
		$maxAvailableAddress = null;

		foreach ($addresses as $currentAddress) {
			if ($startAddress === null) {
				$startAddress = $currentAddress->getAddress();
			}

			$nextAvailableAddress = $currentAddress->getAddress() + $currentAddress->getSize();

			// In case next address is smaller than previous address with its size
			// we need to make sure that quantity does not change as those addresses overlap
			if ($maxAvailableAddress === null || $nextAvailableAddress > $maxAvailableAddress) {
				$maxAvailableAddress = $nextAvailableAddress;
			}

			$previousQuantity = $quantity;
			$quantity += $currentAddress->getSize();

			if (
				$quantity >= $maxAddressesPerModbusRequest
				|| ($previousAddress !== null && ($currentAddress->getAddress() - $previousAddress->getAddress()) > $previousAddress->getSize())
			) {
				if ($currentAddress instanceof Requests\ReadCoilAddress) {
					$result[] = new Requests\ReadCoilsRequest($chunk, $startAddress, $previousQuantity);

				} elseif ($currentAddress instanceof Requests\ReadDiscreteInputAddress) {
					$result[] = new Requests\ReadDiscreteInputsRequest(
						$chunk,
						$startAddress,
						$previousQuantity,
					);

				} elseif ($currentAddress instanceof Requests\ReadHoldingRegisterAddress) {
					$result[] = new Requests\ReadHoldingsRegistersRequest(
						$chunk,
						$startAddress,
						$previousQuantity,
					);

				} elseif ($currentAddress instanceof Requests\ReadInputRegisterAddress) {
					$result[] = new Requests\ReadInputsRegistersRequest(
						$chunk,
						$startAddress,
						$previousQuantity,
					);
				}

				$startAddress = $currentAddress->getAddress();
				$quantity = $currentAddress->getSize();
				$chunk = [];
				$maxAvailableAddress = null;
			}

			$previousAddress = $currentAddress;

			$chunk[] = $currentAddress;
		}

		if ($chunk[0] instanceof Requests\ReadCoilAddress) {
			$result[] = new Requests\ReadCoilsRequest($chunk, $startAddress, $quantity);

		} elseif ($chunk[0] instanceof Requests\ReadDiscreteInputAddress) {
			$result[] = new Requests\ReadDiscreteInputsRequest($chunk, $startAddress, $quantity);

		} elseif ($chunk[0] instanceof Requests\ReadHoldingRegisterAddress) {
			$result[] = new Requests\ReadHoldingsRegistersRequest($chunk, $startAddress, $quantity);

		} elseif ($chunk[0] instanceof Requests\ReadInputRegisterAddress) {
			$result[] = new Requests\ReadInputsRegistersRequest($chunk, $startAddress, $quantity);
		}

		return $result;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function processDigitalRegistersResponse(
		Requests\ReadResponse $request,
		API\Responses\ReadDigitalInputs $response,
		MetadataDocuments\DevicesModule\Device $device,
	): void
	{
		foreach ($response->getRegisters() as $address => $value) {
			if ($request instanceof Requests\ReadCoilsRequest) {
				$channel = $this->deviceHelper->findChannelByType(
					$device,
					$address,
					Types\ChannelType::get(Types\ChannelType::COIL),
				);

			} elseif ($request instanceof Requests\ReadDiscreteInputsRequest) {
				$channel = $this->deviceHelper->findChannelByType(
					$device,
					$address,
					Types\ChannelType::get(Types\ChannelType::DISCRETE_INPUT),
				);

			} else {
				continue;
			}

			if ($channel === null) {
				throw new Exceptions\InvalidState(
					'Register could not be loaded. Received data could not be handled',
				);
			}

			$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::VALUE);

			$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
				$findChannelPropertyQuery,
				MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
			);

			if (!$property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
				throw new Exceptions\InvalidState(
					'Register value storage could not be loaded. Received data could not be handled',
				);
			}

			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreChannelPropertyState::class,
					[
						'connector' => $device->getConnector(),
						'device' => $device->getId(),
						'channel' => $channel->getId(),
						'property' => $property->getId(),
						'value' => $value,
					],
				),
			);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function processAnalogRegistersResponse(
		Requests\ReadResponse $request,
		API\Responses\ReadAnalogInputs $response,
		MetadataDocuments\DevicesModule\Device $device,
	): void
	{
		$registersBytes = $response->getRegisters();

		foreach ($request->getAddresses() as $requestAddress) {
			if ($request instanceof Requests\ReadHoldingsRegistersRequest) {
				$channel = $this->deviceHelper->findChannelByType(
					$device,
					$requestAddress->getAddress(),
					Types\ChannelType::get(Types\ChannelType::HOLDING_REGISTER),
				);
			} elseif ($request instanceof Requests\ReadInputsRegistersRequest) {
				$channel = $this->deviceHelper->findChannelByType(
					$device,
					$requestAddress->getAddress(),
					Types\ChannelType::get(Types\ChannelType::INPUT_REGISTER),
				);
			} else {
				continue;
			}

			if ($channel === null) {
				throw new Exceptions\InvalidState(
					'Register could not be loaded. Received data could not be handled',
				);
			}

			$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::VALUE);

			$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
				$findChannelPropertyQuery,
				MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
			);

			if (!$property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
				throw new Exceptions\InvalidState(
					'Register value storage could not be loaded. Received data could not be handled',
				);
			}

			$deviceExpectedDataType = $this->transformer->determineDeviceReadDataType(
				$property->getDataType(),
				$property->getFormat(),
			);

			$registerBytes = [];

			if (
				$deviceExpectedDataType === MetadataTypes\DataType::CHAR
				|| $deviceExpectedDataType === MetadataTypes\DataType::UCHAR
				|| $deviceExpectedDataType === MetadataTypes\DataType::SHORT
				|| $deviceExpectedDataType === MetadataTypes\DataType::USHORT
			) {
				$registerBytes = array_splice($registersBytes, 0, 2);
			} elseif (
				$deviceExpectedDataType === MetadataTypes\DataType::INT
				|| $deviceExpectedDataType === MetadataTypes\DataType::UINT
				|| $deviceExpectedDataType === MetadataTypes\DataType::FLOAT
			) {
				$registerBytes = array_splice($registersBytes, 0, 4);
			}

			$value = null;

			if (
				$deviceExpectedDataType === MetadataTypes\DataType::CHAR
				|| $deviceExpectedDataType === MetadataTypes\DataType::SHORT
				|| $deviceExpectedDataType === MetadataTypes\DataType::INT
			) {
				$value = $this->transformer->unpackSignedInt(
					$registerBytes,
					$this->deviceHelper->getByteOrder($device),
				);
			} elseif (
				$deviceExpectedDataType === MetadataTypes\DataType::UCHAR
				|| $deviceExpectedDataType === MetadataTypes\DataType::USHORT
				|| $deviceExpectedDataType === MetadataTypes\DataType::UINT
			) {
				$value = $this->transformer->unpackUnsignedInt(
					$registerBytes,
					$this->deviceHelper->getByteOrder($device),
				);
			} elseif ($deviceExpectedDataType === MetadataTypes\DataType::FLOAT) {
				$value = $this->transformer->unpackFloat($registerBytes, $this->deviceHelper->getByteOrder($device));
			}

			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreChannelPropertyState::class,
					[
						'connector' => $device->getConnector(),
						'device' => $device->getId(),
						'channel' => $channel->getId(),
						'property' => $property->getId(),
						'value' => $value,
					],
				),
			);
		}
	}

}
