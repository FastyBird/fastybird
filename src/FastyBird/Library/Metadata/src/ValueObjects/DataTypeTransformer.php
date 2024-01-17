<?php declare(strict_types = 1);

/**
 * DataTypeTransformer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     ValueObjects
 * @since          1.0.0
 *
 * @date           17.01.24
 */

namespace FastyBird\Library\Metadata\ValueObjects;

use Contributte\Monolog;
use DateTimeInterface;
use FastyBird\Library\Metadata\Types;
use FastyBird\Library\Metadata\Utilities;
use function boolval;
use function in_array;

/**
 * Compatible data type value transformer
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     ValueObjects
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DataTypeTransformer
{

	public function __construct(
		private readonly bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null $value,
		private readonly Types\DataType $source,
		private readonly Types\DataType $destination,
	)
	{
	}

	public function convert(): bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null
	{
		if ($this->destination->equalsValue($this->source->getValue())) {
			return $this->value;
		}

		if (
			in_array(
				$this->destination->getValue(),
				[
					Types\DataType::DATA_TYPE_CHAR,
					Types\DataType::DATA_TYPE_UCHAR,
					Types\DataType::DATA_TYPE_SHORT,
					Types\DataType::DATA_TYPE_USHORT,
					Types\DataType::DATA_TYPE_INT,
					Types\DataType::DATA_TYPE_UINT,
					Types\DataType::DATA_TYPE_FLOAT,
				],
				true,
			)
			&& in_array(
				$this->source->getValue(),
				[
					Types\DataType::DATA_TYPE_CHAR,
					Types\DataType::DATA_TYPE_UCHAR,
					Types\DataType::DATA_TYPE_SHORT,
					Types\DataType::DATA_TYPE_USHORT,
					Types\DataType::DATA_TYPE_INT,
					Types\DataType::DATA_TYPE_UINT,
					Types\DataType::DATA_TYPE_FLOAT,
				],
				true,
			)
		) {
			return $this->value;
		}

		if ($this->destination->equalsValue(Types\DataType::DATA_TYPE_BOOLEAN)) {
			if (
				$this->source->equalsValue(Types\DataType::DATA_TYPE_SWITCH)
				&& (
					$this->value instanceof Types\SwitchPayload
					|| $this->value === null
				)
			) {
				return $this->value?->equalsValue(Types\SwitchPayload::PAYLOAD_ON) ?? false;
			} elseif (
				$this->source->equalsValue(Types\DataType::DATA_TYPE_BUTTON)
				&& (
					$this->value instanceof Types\ButtonPayload
					|| $this->value === null
				)
			) {
				return $this->value?->equalsValue(Types\ButtonPayload::PAYLOAD_PRESSED) ?? false;
			} elseif (
				$this->source->equalsValue(Types\DataType::DATA_TYPE_COVER)
				&& (
					$this->value instanceof Types\CoverPayload
					|| $this->value === null
				)
			) {
				return $this->value?->equalsValue(Types\CoverPayload::PAYLOAD_OPEN) ?? false;
			}
		}

		if ($this->source->equalsValue(Types\DataType::DATA_TYPE_BOOLEAN)) {
			if ($this->destination->equalsValue(Types\DataType::DATA_TYPE_SWITCH)) {
				return Types\SwitchPayload::get(
					boolval($this->value)
						? Types\SwitchPayload::PAYLOAD_ON
						: Types\SwitchPayload::PAYLOAD_OFF,
				);
			} elseif ($this->destination->equalsValue(Types\DataType::DATA_TYPE_BUTTON)) {
				return Types\ButtonPayload::get(
					boolval($this->value)
						? Types\ButtonPayload::PAYLOAD_PRESSED
						: Types\ButtonPayload::PAYLOAD_RELEASED,
				);
			} elseif ($this->destination->equalsValue(Types\DataType::DATA_TYPE_COVER)) {
				return Types\CoverPayload::get(
					boolval($this->value)
						? Types\CoverPayload::PAYLOAD_OPEN
						: Types\CoverPayload::PAYLOAD_CLOSE,
				);
			}
		}

		Monolog\LoggerHolder::getInstance()->getLogger()->warning(
			'Parent property value could not be transformed to mapped property value',
			[
				'source' => Types\ModuleSource::SOURCE_MODULE_DEVICES,
				'type' => 'data-type-transformer',
				'source_data_type' => $this->source->getValue(),
				'destination_data_type' => $this->destination->getValue(),
				'value' => Utilities\Value::flattenValue($this->value),
			],
		);

		return $this->value;
	}

}
