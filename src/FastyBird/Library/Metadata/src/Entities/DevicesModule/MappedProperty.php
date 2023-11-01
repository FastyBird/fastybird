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

	private string|int|bool|float|null $actualValue;

	private string|int|bool|float|null $previousValue;

	private string|int|bool|float|null $expectedValue;

	private string|bool|null $pending;

	private string|int|bool|float|null $value;

	private string|int|bool|float|null $default;

	/**
	 * @param array<int, string>|array<int, string|int|float|array<int, string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 * @param bool|null $pending
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
		float|null $step = null,
		float|bool|int|string|null $actualValue = null,
		float|bool|int|string|null $previousValue = null,
		float|bool|int|string|null $expectedValue = null,
		bool|string|null $pending = null,
		private readonly bool|null $valid = null,
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

		$this->actualValue = $actualValue;
		$this->previousValue = $previousValue;
		$this->expectedValue = $expectedValue;
		$this->pending = $pending;

		$this->value = $value;
		$this->default = $default;
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
		return $this->pending !== null;
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
