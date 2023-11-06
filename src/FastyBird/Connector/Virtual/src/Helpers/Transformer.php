<?php declare(strict_types = 1);

/**
 * Transformer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           21.10.23
 */

namespace FastyBird\Connector\Virtual\Helpers;

use DateTimeInterface;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use function boolval;

/**
 * Value transformers
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Transformer
{

	/**
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 */
	public static function fromMappedParent(
		DevicesEntities\Channels\Properties\Mapped $property,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		$parent = $property->getParent();

		if (!$parent instanceof DevicesEntities\Channels\Properties\Dynamic) {
			return $value;
		}

		if ($property->getDataType()->equals($parent->getDataType())) {
			return $value;
		}

		if ($property->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
			if (
				$parent->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
				&& (
					$value instanceof MetadataTypes\SwitchPayload
					|| $value === null
				)
			) {
				return $value?->equalsValue(MetadataTypes\SwitchPayload::PAYLOAD_ON) ?? false;
			} elseif (
				$parent->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
				&& (
					$value instanceof MetadataTypes\ButtonPayload
					|| $value === null
				)
			) {
				return $value?->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_PRESSED) ?? false;
			}
		}

		throw new Exceptions\InvalidState(
			'Value received from mapped property could not be transformed into virtual device',
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 */
	public static function toMappedParent(
		DevicesEntities\Channels\Properties\Mapped $property,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		$parent = $property->getParent();

		if (!$parent instanceof DevicesEntities\Channels\Properties\Dynamic) {
			return $value;
		}

		if ($property->getDataType()->equals($parent->getDataType())) {
			return $value;
		}

		if ($property->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
			if ($parent->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)) {
				return MetadataTypes\SwitchPayload::get(
					boolval($value)
						? MetadataTypes\SwitchPayload::PAYLOAD_ON
						: MetadataTypes\SwitchPayload::PAYLOAD_OFF,
				);
			} elseif ($parent->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)) {
				return MetadataTypes\ButtonPayload::get(
					boolval($value)
						? MetadataTypes\ButtonPayload::PAYLOAD_PRESSED
						: MetadataTypes\ButtonPayload::PAYLOAD_RELEASED,
				);
			}
		}

		throw new Exceptions\InvalidState(
			'Value received from virtual device could not be transformed into mapped property',
		);
	}

}
