<?php declare(strict_types = 1);

/**
 * Characteristic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           13.09.22
 */

namespace FastyBird\Connector\HomeKit\Protocol\Characteristics;

use DateTimeInterface;
use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Helpers;
use FastyBird\Connector\HomeKit\Protocol;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use Nette;
use Ramsey\Uuid;
use function array_merge;
use function in_array;
use function sprintf;
use function strval;

/**
 * Represents a HAP characteristic, the smallest unit of the smart home
 *
 * A HAP characteristic is some measurement or state, like battery status or
 * the current temperature. Characteristics are contained in services.
 * Each characteristic has a unique type UUID and a set of properties,
 * like format, min and max values, valid values and others.
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Characteristic
{

	use Nette\SmartObject;

	private const DEFAULT_MAX_LENGTH = 64;

	private const ABSOLUTE_MAX_LENGTH = 256;

	private const VIRTUAL_CHARACTERISTIC_UID = '00000000-0000-0000-0000-000000000000';

	private const ALWAYS_NULL
		= [
			'00000073-0000-1000-8000-0026BB765291', // PROGRAMMABLE SWITCH
		];

	private const IMMEDIATE_NOTIFY
		= [
			'00000126-0000-1000-8000-0026BB765291', // BUTTON
			'00000073-0000-1000-8000-0026BB765291', // PROGRAMMABLE SWITCH
		];

	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	private bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $actualValue = null;

	private bool $valid = true;

	/**
	 * @param array<string> $permissions
	 * @param array<int>|null $validValues
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(
		private readonly Uuid\UuidInterface $typeId,
		private readonly string $name,
		private readonly Types\DataType $dataType,
		private readonly array $permissions,
		private readonly Protocol\Services\Service $service,
		private readonly MetadataDocuments\DevicesModule\ChannelProperty|null $property = null,
		private readonly array|null $validValues = [],
		private readonly int|null $maxLength = null,
		private readonly float|null $minValue = null,
		private readonly float|null $maxValue = null,
		private readonly float|null $minStep = null,
		private readonly Types\CharacteristicUnit|null $unit = null,
	)
	{
		if ($maxLength !== null && $maxLength > self::ABSOLUTE_MAX_LENGTH) {
			throw new Exceptions\InvalidArgument('Characteristic max length exceeded allowed maximum');
		}
	}

	public function getTypeId(): Uuid\UuidInterface
	{
		return $this->typeId;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDataType(): Types\DataType
	{
		return $this->dataType;
	}

	/**
	 * @return array<string>
	 */
	public function getPermissions(): array
	{
		return $this->permissions;
	}

	/**
	 * @return array<int>|null
	 */
	public function getValidValues(): array|null
	{
		return $this->validValues;
	}

	public function getMinValue(): float|null
	{
		return $this->minValue;
	}

	public function getMaxValue(): float|null
	{
		return $this->maxValue;
	}

	public function getMinStep(): float|null
	{
		return $this->minStep;
	}

	public function getMaxLength(): int|null
	{
		return $this->maxLength;
	}

	public function getService(): Protocol\Services\Service
	{
		return $this->service;
	}

	public function getProperty(): MetadataDocuments\DevicesModule\ChannelProperty|null
	{
		return $this->property;
	}
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		return $this->actualValue;
	}

	public function setValue(
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): void
	{
		$this->actualValue = $value;
	}

	public function setActualValue(
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): void
	{
		$this->setValue($value);

		$this->service->recalculateValues($this, true);
	}

	public function setExpectedValue(
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): void
	{
		$this->setValue($value);

		$this->service->recalculateValues($this, false);
	}

	public function isAlwaysNull(): bool
	{
		return in_array($this->typeId->toString(), self::ALWAYS_NULL, true);
	}

	public function immediateNotify(): bool
	{
		return in_array($this->typeId->toString(), self::IMMEDIATE_NOTIFY, true);
	}

	public function isVirtual(): bool
	{
		return $this->typeId->toString() === self::VIRTUAL_CHARACTERISTIC_UID;
	}

	public function setValid(bool $state): void
	{
		$this->valid = $state;
	}

	public function isValid(): bool
	{
		return $this->valid;
	}

	/**
	 * @return array<string, (int|array<int>|float|string)>
	 */
	public function getMeta(): array
	{
		$meta = [
			Types\Representation::FORMAT => strval($this->dataType->getValue()),
		];

		if (
			$this->dataType->equalsValue(Types\DataType::INT)
			|| $this->dataType->equalsValue(Types\DataType::UINT8)
			|| $this->dataType->equalsValue(Types\DataType::UINT16)
			|| $this->dataType->equalsValue(Types\DataType::UINT32)
			|| $this->dataType->equalsValue(Types\DataType::UINT64)
			|| $this->dataType->equalsValue(Types\DataType::FLOAT)
		) {
			if ($this->maxValue !== null) {
				$meta[Types\Representation::MAX_VALUE] = $this->maxValue;
			}

			if ($this->minValue !== null) {
				$meta[Types\Representation::MIN_VALUE] = $this->minValue;
			}

			if ($this->minStep !== null) {
				$meta[Types\Representation::MIN_STEP] = $this->minStep;
			}

			if ($this->unit !== null) {
				$meta[Types\Representation::UNIT] = strval($this->unit->getValue());
			}
		}

		if ($this->validValues !== null) {
			$meta[Types\Representation::VALID_VALUES] = $this->validValues;
		}

		if ($this->dataType->equalsValue(Types\DataType::STRING) && $this->maxLength !== null) {
			if ($this->maxLength !== self::DEFAULT_MAX_LENGTH) {
				$meta[Types\Representation::MAX_LEN] = $this->maxLength;
			}
		}

		return $meta;
	}

	/**
	 * Create a HAP representation of this Characteristic
	 * Used for json serialization
	 *
	 * @return array<string, (bool|float|int|array<int>|string|array<string>|null)>
	 *
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function toHap(): array
	{
		$hapRepresentation = [
			Types\Representation::IID => $this->service->getAccessory()->getIidManager()->getIid($this),
			Types\Representation::TYPE => Helpers\Protocol::uuidToHapType($this->typeId),
			Types\Representation::PERM => $this->permissions,
			Types\Representation::FORMAT => strval($this->dataType->getValue()),
		];

		$hapRepresentation = array_merge($hapRepresentation, $this->getMeta());

		if (in_array(Types\CharacteristicPermission::READ, $this->permissions, true)) {
			$hapRepresentation[Types\Representation::VALUE] = Protocol\Transformer::toClient(
				$this->property,
				$this->dataType,
				$this->validValues,
				$this->maxLength,
				$this->minValue,
				$this->maxValue,
				$this->minStep,
				$this->getValue(),
			);
		}

		$hapRepresentation[Types\CharacteristicPermission::NOTIFY] = in_array(
			Types\CharacteristicPermission::NOTIFY,
			$this->permissions,
			true,
		);

		return $hapRepresentation;
	}

	/**
	 * @throws Nette\Utils\JsonException
	 */
	public function __toString(): string
	{
		$properties = [
			'permissions' => $this->permissions,
			'format' => $this->dataType->getValue(),
		];

		if ($this->validValues !== null) {
			$properties['valid-values'] = $this->validValues;
		}

		if ($this->minStep !== null) {
			$properties['min-step'] = $this->minStep;
		}

		if ($this->minValue !== null) {
			$properties['min-value'] = $this->minValue;
		}

		if ($this->maxValue !== null) {
			$properties['max-value'] = $this->maxValue;
		}

		if ($this->unit !== null) {
			$properties['unit'] = $this->unit->getValue();
		}

		return sprintf(
			'<characteristic name=%s value=%s properties=%s>',
			$this->name,
			MetadataUtilities\Value::flattenValue($this->getValue()),
			Nette\Utils\Json::encode($properties),
		);
	}

}