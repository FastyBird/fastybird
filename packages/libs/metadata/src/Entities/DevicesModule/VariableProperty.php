<?php declare(strict_types = 1);

/**
 * VariableProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Metadata\Entities\DevicesModule;

use function array_merge;

/**
 * Variable property entity
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class VariableProperty extends Property
{

	private string|int|bool|float|null $value;

	private string|int|bool|float|null $default;

	/**
	 * @param Array<int, string>|Array<int, string|int|float|Array<int, string|int|float>|null>|Array<int, Array<int, string|Array<int, string|int|float|bool>|null>>|null $format
	 */
	public function __construct(
		string $id,
		string $type,
		string $identifier,
		string|null $name,
		bool $settable,
		bool $queryable,
		string $dataType,
		string|null $unit = null,
		array|null $format = null,
		string|int|float|null $invalid = null,
		int|null $numberOfDecimals = null,
		float|bool|int|string|null $value = null,
		float|bool|int|string|null $default = null,
		string|null $owner = null,
	)
	{
		parent::__construct(
			$id,
			$type,
			$identifier,
			$name,
			$settable,
			$queryable,
			$dataType,
			$unit,
			$format,
			$invalid,
			$numberOfDecimals,
			$owner,
		);

		$this->value = $value;
		$this->default = $default;
	}

	public function getValue(): float|bool|int|string|null
	{
		return $this->value;
	}

	public function getDefault(): float|bool|int|string|null
	{
		return $this->default;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'value' => $this->getValue(),
			'default' => $this->getDefault(),
		]);
	}

	/**
	 * @return Array<string, mixed>
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
