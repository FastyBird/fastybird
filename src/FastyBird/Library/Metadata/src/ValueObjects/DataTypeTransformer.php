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
					Types\DataType::CHAR,
					Types\DataType::UCHAR,
					Types\DataType::SHORT,
					Types\DataType::USHORT,
					Types\DataType::INT,
					Types\DataType::UINT,
					Types\DataType::FLOAT,
				],
				true,
			)
			&& in_array(
				$this->source->getValue(),
				[
					Types\DataType::CHAR,
					Types\DataType::UCHAR,
					Types\DataType::SHORT,
					Types\DataType::USHORT,
					Types\DataType::INT,
					Types\DataType::UINT,
					Types\DataType::FLOAT,
				],
				true,
			)
		) {
			return $this->value;
		}

		if ($this->destination->equalsValue(Types\DataType::BOOLEAN)) {
			if (
				$this->source->equalsValue(Types\DataType::SWITCH)
				&& (
					$this->value instanceof Types\SwitchPayload
					|| $this->value === null
				)
			) {
				return $this->value?->equalsValue(Types\SwitchPayload::ON) ?? false;
			} elseif (
				$this->source->equalsValue(Types\DataType::BUTTON)
				&& (
					$this->value instanceof Types\ButtonPayload
					|| $this->value === null
				)
			) {
				return $this->value?->equalsValue(Types\ButtonPayload::PRESSED) ?? false;
			} elseif (
				$this->source->equalsValue(Types\DataType::COVER)
				&& (
					$this->value instanceof Types\CoverPayload
					|| $this->value === null
				)
			) {
				return $this->value?->equalsValue(Types\CoverPayload::OPEN) ?? false;
			}
		}

		if ($this->source->equalsValue(Types\DataType::BOOLEAN)) {
			if ($this->destination->equalsValue(Types\DataType::SWITCH)) {
				return Types\SwitchPayload::get(
					boolval($this->value)
						? Types\SwitchPayload::ON
						: Types\SwitchPayload::OFF,
				);
			} elseif ($this->destination->equalsValue(Types\DataType::BUTTON)) {
				return Types\ButtonPayload::get(
					boolval($this->value)
						? Types\ButtonPayload::PRESSED
						: Types\ButtonPayload::RELEASED,
				);
			} elseif ($this->destination->equalsValue(Types\DataType::COVER)) {
				return Types\CoverPayload::get(
					boolval($this->value)
						? Types\CoverPayload::OPEN
						: Types\CoverPayload::CLOSE,
				);
			}
		}

		Monolog\LoggerHolder::getInstance()->getLogger()->warning(
			'Parent property value could not be transformed to mapped property value',
			[
				'source' => Types\ModuleSource::DEVICES,
				'type' => 'data-type-transformer',
				'source_data_type' => $this->source->getValue(),
				'destination_data_type' => $this->destination->getValue(),
				'value' => Utilities\Value::flattenValue($this->value),
			],
		);

		return $this->value;
	}

}
