<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           07.12.21
 */

namespace FastyBird\Connector\Modbus\Entities\Connectors;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\Modbus;
use FastyBird\Connector\Modbus\Entities;
use FastyBird\Connector\Modbus\Exceptions;
use FastyBird\Connector\Modbus\Types;
use FastyBird\Library\Application\Entities\Mapping as ApplicationMapping;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use function is_numeric;
use function is_string;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class Connector extends DevicesEntities\Connectors\Connector
{

	public const TYPE = 'modbus-connector';

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): MetadataTypes\Sources\Connector
	{
		return MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::MODBUS);
	}

	/**
	 * @return array<Entities\Devices\Device>
	 */
	public function getDevices(): array
	{
		$devices = [];

		foreach (parent::getDevices() as $device) {
			if ($device instanceof Entities\Devices\Device) {
				$devices[] = $device;
			}
		}

		return $devices;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function addDevice(DevicesEntities\Devices\Device $device): void
	{
		if (!$device instanceof Entities\Devices\Device) {
			throw new Exceptions\InvalidArgument('Provided device type is not valid');
		}

		parent::addDevice($device);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getClientMode(): Types\ClientMode
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::CLIENT_MODE
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& Types\ClientMode::isValidValue($property->getValue())
		) {
			return Types\ClientMode::get($property->getValue());
		}

		throw new Exceptions\InvalidState('Connector mode is not configured');
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getByteSize(): Types\ByteSize
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::RTU_BYTE_SIZE
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_numeric($property->getValue())
			&& Types\ByteSize::isValidValue($property->getValue())
		) {
			return Types\ByteSize::get($property->getValue());
		}

		return Types\ByteSize::get(Types\ByteSize::SIZE_8);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getBaudRate(): Types\BaudRate
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::RTU_BAUD_RATE
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_numeric($property->getValue())
			&& Types\BaudRate::isValidValue($property->getValue())
		) {
			return Types\BaudRate::get($property->getValue());
		}

		return Types\BaudRate::get(Types\BaudRate::RATE_9600);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getParity(): Types\Parity
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::RTU_PARITY
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_numeric($property->getValue())
			&& Types\Parity::isValidValue($property->getValue())
		) {
			return Types\Parity::get($property->getValue());
		}

		return Types\Parity::get(Types\Parity::NONE);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getStopBits(): Types\StopBits
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::RTU_STOP_BITS
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_numeric($property->getValue())
			&& Types\StopBits::isValidValue($property->getValue())
		) {
			return Types\StopBits::get($property->getValue());
		}

		return Types\StopBits::get(Types\StopBits::ONE);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getRtuInterface(): string
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::RTU_INTERFACE
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_string($property->getValue())
		) {
			return $property->getValue();
		}

		return Modbus\Constants::DEFAULT_RTU_SERIAL_INTERFACE;
	}

}
