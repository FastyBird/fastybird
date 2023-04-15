<?php declare(strict_types = 1);

/**
 * VariableProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Entities\DevicesModule;

use FastyBird\Library\Metadata\Exceptions;
use function array_merge;

/**
 * Variable property entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class VariableProperty extends Property
{

	private string|int|bool|float|null $value;

	private string|int|bool|float|null $default;

	/**
	 * @param array<int, string>|array<int, string|int|float|array<int, string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 */
	public function __construct(
		string $id,
		string $type,
		string $category,
		string $identifier,
		string|null $name,
		bool $settable,
		bool $queryable,
		string $dataType,
		string|null $unit = null,
		array|null $format = null,
		string|int|float|null $invalid = null,
		int|null $scale = null,
		int|null $step = null,
		float|bool|int|string|null $value = null,
		float|bool|int|string|null $default = null,
		string|null $owner = null,
	)
	{
		parent::__construct(
			$id,
			$type,
			$category,
			$identifier,
			$name,
			$settable,
			$queryable,
			$dataType,
			$unit,
			$format,
			$invalid,
			$scale,
			$step,
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
	 * @return array<string, mixed>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
