<?php declare(strict_types = 1);

/**
 * PropertyAttribute.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           24.02.20
 */

namespace FastyBird\Connector\FbMqtt\Entities\Messages;

use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Metadata\Types as MetadataTypes;
use Nette;
use Nette\Utils;
use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function explode;
use function in_array;
use function is_numeric;
use function sprintf;
use function strtolower;

/**
 * Device or channel property attribute
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class PropertyAttribute
{

	use Nette\SmartObject;

	public const NAME = 'name';

	public const SETTABLE = 'settable';

	public const QUERYABLE = 'queryable';

	public const DATA_TYPE = 'data-type';

	public const FORMAT = 'format';

	public const UNIT = 'unit';

	public const ALLOWED_ATTRIBUTES = [
		self::NAME,
		self::SETTABLE,
		self::QUERYABLE,
		self::DATA_TYPE,
		self::FORMAT,
		self::UNIT,
	];

	public const FORMAT_ALLOWED_PAYLOADS = [
		'rgb',
		'hsv',
	];

	/** @var string|Array<string>|Array<float>|Array<null>|bool|MetadataTypes\DataType|null */
	private MetadataTypes\DataType|string|array|bool|null $value = null;

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\ParseMessage
	 */
	public function __construct(private readonly string $attribute, string $value)
	{
		if (!in_array($attribute, self::ALLOWED_ATTRIBUTES, true)) {
			throw new Exceptions\InvalidArgument(
				sprintf('Provided property parameter "%s" is not in allowed range', $attribute),
			);
		}

		$this->parseValue($value);
	}

	public function getAttribute(): string
	{
		return $this->attribute;
	}

	/**
	 * @return string|Array<string>|Array<float>|Array<null>|bool|MetadataTypes\DataType|null
	 */
	public function getValue(): string|array|bool|MetadataTypes\DataType|null
	{
		if ($this->value === null) {
			return null;
		}

		if (
			$this->attribute === self::SETTABLE
			|| $this->attribute === self::QUERYABLE
		) {
			return $this->value === FbMqtt\Constants::PAYLOAD_BOOL_TRUE_VALUE;
		}

		return $this->value;
	}

	/**
	 * @return Array<mixed>
	 */
	public function toArray(): array
	{
		return [
			'attribute' => $this->getAttribute(),
			'value' => $this->getValue(),
		];
	}

	/**
	 * @throws Exceptions\ParseMessage
	 */
	private function parseValue(string $value): void
	{
		if (
			$this->getAttribute() === self::SETTABLE
			|| $this->getAttribute() === self::QUERYABLE
		) {
			$this->value = $value === FbMqtt\Constants::PAYLOAD_BOOL_TRUE_VALUE
				? FbMqtt\Constants::PAYLOAD_BOOL_TRUE_VALUE
				: FbMqtt\Constants::PAYLOAD_BOOL_FALSE_VALUE;

		} elseif ($this->getAttribute() === self::NAME) {
			$this->value = Helpers\Payload::cleanName($value);

		} elseif ($this->getAttribute() === self::DATA_TYPE) {
			if (!MetadataTypes\DataType::isValidValue($value)) {
				throw new Exceptions\ParseMessage('Provided payload is not valid');
			}

			$this->value = MetadataTypes\DataType::get($value);

		} elseif ($this->getAttribute() === self::FORMAT) {
			if (Utils\Strings::contains($value, ':')) {
				[$start, $end] = explode(':', $value) + [null, null];

				$start = $start === '' ? null : $start;
				$end = $end === '' ? null : $end;

				if ($start !== null && is_numeric($start) === false) {
					throw new Exceptions\ParseMessage('Provided payload is not valid');
				}

				if ($end !== null && is_numeric($end) === false) {
					throw new Exceptions\ParseMessage('Provided payload is not valid');
				}

				if ($start !== null) {
					$start = (float) $start;
				}

				if ($end !== null) {
					$end = (float) $end;
				}

				if ($start !== null && $end !== null && $start > $end) {
					throw new Exceptions\ParseMessage('Provided payload is not valid');
				}

				$this->value = [$start, $end];

			} elseif (Utils\Strings::contains($value, ',')) {
				$value = array_filter(
					array_map('trim', explode(',', strtolower($value))),
					static fn ($item): bool => $item !== '',
				);

				$value = array_values($value);

				$this->value = array_unique($value);

			} elseif ($value === FbMqtt\Constants::VALUE_NOT_SET || $value === '') {
				$this->value = null;

			} elseif (!in_array($value, self::FORMAT_ALLOWED_PAYLOADS, true)) {
				throw new Exceptions\ParseMessage('Provided payload is not valid');
			}
		} else {
			$this->value = $value === FbMqtt\Constants::VALUE_NOT_SET || $value === '' ? null : $value;
		}
	}

}
