<?php declare(strict_types = 1);

/**
 * CloudDeviceDataPoint.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           27.08.22
 */

namespace FastyBird\Connector\Tuya\Entities\Messages;

use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Orisai\ObjectMapper;

/**
 * Discovered cloud device data point entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CloudDeviceDataPoint implements Entity
{

	/**
	 * @param array<string> $range
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $device,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $code,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $name,
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: MetadataTypes\DataType::class)]
		#[ObjectMapper\Modifiers\FieldName('data_type')]
		private readonly MetadataTypes\DataType $dataType,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $unit,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\StringValue(notEmpty: true),
		)]
		private readonly array $range,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly int|float|null $min,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly int|float|null $max,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly int|float|null $step,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly int|float|null $scale,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $queryable,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $settable,
	)
	{
	}

	public function getDevice(): string
	{
		return $this->device;
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDataType(): MetadataTypes\DataType
	{
		return $this->dataType;
	}

	public function getUnit(): string|null
	{
		return $this->unit;
	}

	/**
	 * @return array<string>
	 */
	public function getRange(): array
	{
		return $this->range;
	}

	public function getMin(): float|int|null
	{
		return $this->min;
	}

	public function getMax(): float|int|null
	{
		return $this->max;
	}

	public function getStep(): float|int|null
	{
		return $this->step;
	}

	public function getScale(): float|int|null
	{
		return $this->scale;
	}

	public function isQueryable(): bool
	{
		return $this->queryable;
	}

	public function isSettable(): bool
	{
		return $this->settable;
	}

	/**
	 * @return array<int, array<int, (string|int|float|null)>>|array<string>|null
	 */
	public function getFormat(): array|null
	{
		if (
			(
				$this->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_CHAR)
				|| $this->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_UCHAR)
				|| $this->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_SHORT)
				|| $this->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_USHORT)
				|| $this->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_INT)
				|| $this->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_UINT)
				|| $this->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_FLOAT)
			) && (
				$this->getMin() !== null
				|| $this->getMax() !== null
			)
		) {
			return [
				[
					MetadataTypes\DataTypeShort::DATA_TYPE_FLOAT,
					$this->getMin(),
				],
				[
					MetadataTypes\DataTypeShort::DATA_TYPE_FLOAT,
					$this->getMax(),
				],
			];
		} elseif (
			$this->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)
			&& $this->getRange() !== []
		) {
			return $this->getRange();
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'device' => $this->getDevice(),

			'code' => $this->getCode(),
			'name' => $this->getName(),
			'data_type' => $this->getDataType()->getValue(),
			'unit' => $this->getUnit(),
			'range' => $this->getRange(),
			'min' => $this->getMin(),
			'max' => $this->getMax(),
			'step' => $this->getStep(),
			'scale' => $this->getScale(),
			'queryable' => $this->isQueryable(),
			'settable' => $this->isSettable(),
		];
	}

}
