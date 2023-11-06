<?php declare(strict_types = 1);

/**
 * MappedProperty.php
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

use DateTimeInterface;
use FastyBird\Library\Metadata\Exceptions;
use Nette\Utils;
use Throwable;
use function array_merge;
use function is_bool;
use function is_string;

/**
 * Mapped property entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class MappedProperty extends Property
{

	/**
	 * @param array<int, string>|array<int, string|int|float|array<int, string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 */
	public function __construct(
		string $id,
		string $type,
		string $category,
		string $identifier,
		string|null $name,
		private readonly bool|null $settable,
		private readonly bool|null $queryable,
		string $dataType,
		string|null $unit = null,
		array|null $format = null,
		string|int|float|null $invalid = null,
		int|null $scale = null,
		float|null $step = null,
		private readonly float|bool|int|string|null $actualValue = null,
		private readonly float|bool|int|string|null $previousValue = null,
		private readonly float|bool|int|string|null $expectedValue = null,
		private readonly bool|string $pending = false,
		private readonly bool $valid = false,
		private readonly float|bool|int|string|null $value = null,
		private readonly float|bool|int|string|null $default = null,
		string|null $owner = null,
	)
	{
		parent::__construct(
			$id,
			$type,
			$category,
			$identifier,
			$name,
			$dataType,
			$unit,
			$format,
			$invalid,
			$scale,
			$step,
			$owner,
		);
	}

	public function isSettable(): bool
	{
		return $this->settable ?? false;
	}

	public function isQueryable(): bool
	{
		return $this->queryable ?? false;
	}

	public function getActualValue(): float|bool|int|string|null
	{
		return $this->actualValue;
	}

	public function getPreviousValue(): float|bool|int|string|null
	{
		return $this->previousValue;
	}

	public function getExpectedValue(): float|bool|int|string|null
	{
		return $this->expectedValue;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getPending(): bool|string|null
	{
		if (is_string($this->pending)) {
			try {
				$pending = Utils\DateTime::from($this->pending);

				return $pending->format(DateTimeInterface::ATOM);
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidState('Pending value could not be created', $ex->getCode(), $ex);
			}
		}

		return $this->pending;
	}

	public function isPending(): bool|null
	{
		return is_bool($this->pending) ? $this->pending : true;
	}

	public function isValid(): bool|null
	{
		return $this->valid;
	}

	public function getValue(): float|bool|int|string|null
	{
		return $this->value;
	}

	public function getDefault(): float|bool|int|string|null
	{
		return $this->default;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'settable' => $this->isSettable(),
			'queryable' => $this->isQueryable(),
			'actual_value' => $this->getActualValue(),
			'expected_value' => $this->getExpectedValue(),
			'pending' => $this->getPending(),
			'valid' => $this->isValid(),
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
