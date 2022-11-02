<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @since          0.9.0
 *
 * @date           02.01.22
 */

namespace FastyBird\Module\Devices\Hydrators\Properties;

use FastyBird\JsonApi\Hydrators as JsonApiHydrators;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use IPub\JsonAPIDocument;
use function array_map;
use function boolval;
use function count;
use function implode;
use function is_array;
use function is_scalar;
use function strval;

/**
 * Property entity hydrator
 *
 * @template T of Entities\Property
 * @extends  JsonApiHydrators\Hydrator<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Property extends JsonApiHydrators\Hydrator
{

	/** @var Array<int|string, string> */
	protected array $attributes
		= [
			0 => 'identifier',
			1 => 'name',
			2 => 'settable',
			3 => 'queryable',
			4 => 'unit',
			5 => 'format',
			6 => 'invalid',
			7 => 'value',

			'data_type' => 'dataType',
			'number_of_decimals' => 'numberOfDecimals',
		];

	protected function hydrateNameAttribute(JsonAPIDocument\Objects\IStandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('name'))
			|| (string) $attributes->get('name') === ''
		) {
			return null;
		}

		return (string) $attributes->get('name');
	}

	protected function hydrateSettableAttribute(JsonAPIDocument\Objects\IStandardObject $attributes): bool
	{
		return is_scalar($attributes->get('settable')) && boolval($attributes->get('settable'));
	}

	protected function hydrateQueryableAttribute(JsonAPIDocument\Objects\IStandardObject $attributes): bool
	{
		return is_scalar($attributes->get('queryable')) && boolval($attributes->get('queryable'));
	}

	protected function hydrateDataTypeAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes,
	): MetadataTypes\DataType|null
	{
		if (
			!is_scalar($attributes->get('data_type'))
			|| (string) $attributes->get('data_type') === ''
			|| !MetadataTypes\DataType::isValidValue((string) $attributes->get('data_type'))
		) {
			return null;
		}

		return MetadataTypes\DataType::get((string) $attributes->get('data_type'));
	}

	protected function hydrateUnitAttribute(JsonAPIDocument\Objects\IStandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('unit'))
			|| (string) $attributes->get('unit') === ''
		) {
			return null;
		}

		return (string) $attributes->get('unit');
	}

	protected function hydrateFormatAttribute(JsonAPIDocument\Objects\IStandardObject $attributes): string|null
	{
		$rawFormat = $attributes->get('format');
		$rawDataType = $attributes->get('data_type');

		if (is_array($rawFormat)) {
			if (
				is_scalar($rawDataType)
				&& MetadataTypes\DataType::isValidValue((string) $rawDataType)
			) {
				$dataType = MetadataTypes\DataType::get((string) $rawDataType);

				if (
					$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)
					|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
					|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
				) {
					return implode(
						',',
						array_map(
							static fn ($item): string => is_array($item) ? implode(':', $item) : strval($item),
							$rawFormat,
						),
					);
				}

				if (count($rawFormat) === 2) {
					return implode(':', $rawFormat);
				}
			}

			return null;
		} elseif (!is_scalar($rawFormat) || (string) $rawFormat === '') {
			return null;
		}

		return (string) $rawFormat;
	}

	protected function hydrateInvalidAttribute(JsonAPIDocument\Objects\IStandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('invalid'))
			|| (string) $attributes->get('invalid') === ''
		) {
			return null;
		}

		return (string) $attributes->get('invalid');
	}

	protected function hydrateNumberOfDecimalsAttribute(JsonAPIDocument\Objects\IStandardObject $attributes): int|null
	{
		if (
			!is_scalar($attributes->get('number_of_decimals'))
			|| (string) $attributes->get('number_of_decimals') === ''
		) {
			return null;
		}

		return (int) $attributes->get('number_of_decimals');
	}

	protected function hydrateValueAttribute(JsonAPIDocument\Objects\IStandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('value'))
			|| (string) $attributes->get('value') === ''
		) {
			return null;
		}

		return (string) $attributes->get('value');
	}

}
