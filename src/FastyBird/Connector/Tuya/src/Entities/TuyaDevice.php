<?php declare(strict_types = 1);

/**
 * TuyaDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.04.22
 */

namespace FastyBird\Connector\Tuya\Entities;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Library\Application\Doctrine\Mapping as ApplicationMapping;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use function assert;
use function explode;
use function floatval;
use function is_bool;
use function is_numeric;
use function is_string;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class TuyaDevice extends DevicesEntities\Devices\Device
{

	public const TYPE = 'tuya-connector';

	public const STATE_READING_DELAY = 5_000.0;

	public const HEARTBEAT_DELAY = 2_500.0;

	private self|null $gateway = null;

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): MetadataTypes\Sources\Connector
	{
		return MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::TUYA);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getProtocolVersion(): Types\DeviceProtocolVersion
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::PROTOCOL_VERSION
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& Types\DeviceProtocolVersion::isValidValue($property->getValue())
		) {
			return Types\DeviceProtocolVersion::get($property->getValue());
		}

		throw new Exceptions\InvalidState('Device protocol version is not configured');
	}

	public function getGateway(): self|null
	{
		if ($this->gateway === null) {
			$gateway = $this->parents
				->filter(static fn (DevicesEntities\Devices\Device $row): bool => $row instanceof self)
				->first();

			assert($gateway instanceof self || $gateway === false);

			$this->gateway = $gateway !== false ? $gateway : null;
		}

		return $this->gateway;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getNodeId(): string|null
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::NODE_ID
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_string($property->getValue())
		) {
			return $property->getValue();
		}

		return null;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getGatewayId(): string|null
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::GATEWAY_ID
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_string($property->getValue())
		) {
			return $property->getValue();
		}

		return null;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getIpAddress(): string|null
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::IP_ADDRESS
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_string($property->getValue())
		) {
			return $property->getValue();
		}

		return null;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getLocalKey(): string|null
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::LOCAL_KEY
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_string($property->getValue())
		) {
			return $property->getValue();
		}

		return null;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function isEncrypted(): bool
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::ENCRYPTED
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_bool($property->getValue())
		) {
			return $property->getValue();
		}

		return false;
	}

	/**
	 * @return array<string>
	 *
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getExcludedDps(): array
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::READ_STATE_EXCLUDE_DPS
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_string($property->getValue())
		) {
			return explode(',', $property->getValue());
		}

		return [];
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getModel(): string|null
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::MODEL
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_string($property->getValue())
		) {
			return $property->getValue();
		}

		return null;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getMacAddress(): string|null
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::MAC_ADDRESS
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_string($property->getValue())
		) {
			return $property->getValue();
		}

		return null;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getStateReadingDelay(): float
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::STATE_READING_DELAY
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_numeric($property->getValue())
		) {
			return floatval($property->getValue());
		}

		return self::STATE_READING_DELAY;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getHeartbeatDelay(): float
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::HEARTBEAT_DELAY
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_numeric($property->getValue())
		) {
			return floatval($property->getValue());
		}

		return self::HEARTBEAT_DELAY;
	}

}
