<?php declare(strict_types = 1);

/**
 * DataTypeTransformer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ToolsLibrary!
 * @subpackage     Transformers
 * @since          1.0.0
 *
 * @date           17.01.24
 */

namespace FastyBird\Library\Tools\Transformers;

use Contributte\Monolog;
use DateTimeInterface;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;

/**
 * Compatible data type value transformer
 *
 * @package        FastyBird:ToolsLibrary!
 * @subpackage     Transformers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DataTypeTransformer
{

	public function __construct(
		private readonly bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
		private readonly MetadataTypes\DataType $source,
		private readonly MetadataTypes\DataType $destination,
	)
	{
	}

	public function convert(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		if ($this->destination->equalsValue($this->source->getValue())) {
			return $this->value;
		}

		if (
			in_array(
				$this->destination->getValue(),
				[
					MetadataTypes\DataType::CHAR,
					MetadataTypes\DataType::UCHAR,
					MetadataTypes\DataType::SHORT,
					MetadataTypes\DataType::USHORT,
					MetadataTypes\DataType::INT,
					MetadataTypes\DataType::UINT,
					MetadataTypes\DataType::FLOAT,
				],
				true,
			)
			&& in_array(
				$this->source->getValue(),
				[
					MetadataTypes\DataType::CHAR,
					MetadataTypes\DataType::UCHAR,
					MetadataTypes\DataType::SHORT,
					MetadataTypes\DataType::USHORT,
					MetadataTypes\DataType::INT,
					MetadataTypes\DataType::UINT,
					MetadataTypes\DataType::FLOAT,
				],
				true,
			)
		) {
			return $this->value;
		}

		if ($this->destination->equalsValue(MetadataTypes\DataType::BOOLEAN)) {
			if (
				$this->source->equalsValue(MetadataTypes\DataType::SWITCH)
				&& (
					$this->value instanceof MetadataTypes\SwitchPayload
					|| $this->value === null
				)
			) {
				return $this->value?->equalsValue(MetadataTypes\SwitchPayload::ON) ?? false;
			} elseif (
				$this->source->equalsValue(MetadataTypes\DataType::BUTTON)
				&& (
					$this->value instanceof MetadataTypes\ButtonPayload
					|| $this->value === null
				)
			) {
				return $this->value?->equalsValue(MetadataTypes\ButtonPayload::PRESSED) ?? false;
			} elseif (
				$this->source->equalsValue(MetadataTypes\DataType::COVER)
				&& (
					$this->value instanceof MetadataTypes\CoverPayload
					|| $this->value === null
				)
			) {
				return $this->value?->equalsValue(MetadataTypes\CoverPayload::OPEN) ?? false;
			}
		}

		if ($this->source->equalsValue(MetadataTypes\DataType::BOOLEAN)) {
			if ($this->destination->equalsValue(MetadataTypes\DataType::SWITCH)) {
				return MetadataTypes\SwitchPayload::get(
					boolval($this->value)
						? MetadataTypes\SwitchPayload::ON
						: MetadataTypes\SwitchPayload::OFF,
				);
			} elseif ($this->destination->equalsValue(MetadataTypes\DataType::BUTTON)) {
				return MetadataTypes\ButtonPayload::get(
					boolval($this->value)
						? MetadataTypes\ButtonPayload::PRESSED
						: MetadataTypes\ButtonPayload::RELEASED,
				);
			} elseif ($this->destination->equalsValue(MetadataTypes\DataType::COVER)) {
				return MetadataTypes\CoverPayload::get(
					boolval($this->value)
						? MetadataTypes\CoverPayload::OPEN
						: MetadataTypes\CoverPayload::CLOSE,
				);
			}
		}

		Monolog\LoggerHolder::getInstance()->getLogger()->warning(
			'Parent property value could not be transformed to mapped property value',
			[
				'source' => MetadataTypes\Sources\Module::DEVICES,
				'type' => 'data-type-transformer',
				'source_data_type' => $this->source->getValue(),
				'destination_data_type' => $this->destination->getValue(),
				'value' => MetadataUtilities\Value::flattenValue($this->value),
			],
		);

		return $this->value;
	}

}
