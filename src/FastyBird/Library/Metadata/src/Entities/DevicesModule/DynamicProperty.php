<?php declare(strict_types = 1);

/**
 * DynamicProperty.php
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

namespace FastyBird\Library\Metadata\Entities\DevicesModule;

use DateTimeInterface;
use Exception;
use Nette\Utils;
use function array_merge;
use function is_string;

/**
 * Dynamic property entity
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class DynamicProperty extends Property
{

	private string|int|bool|float|null $actualValue;

	private string|int|bool|float|null $previousValue;

	private string|int|bool|float|null $expectedValue;

	private string|bool|null $pending;

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
		float|bool|int|string|null $actualValue = null,
		float|bool|int|string|null $previousValue = null,
		float|bool|int|string|null $expectedValue = null,
		bool|string|null $pending = null,
		private readonly bool|null $valid = null,
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

		$this->actualValue = $actualValue;
		$this->previousValue = $previousValue;
		$this->expectedValue = $expectedValue;
		$this->pending = $pending;
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
	 * @throws Exception
	 */
	public function getPending(): bool|string|null
	{
		if (is_string($this->pending)) {
			$pending = Utils\DateTime::from($this->pending);

			return $pending->format(DateTimeInterface::ATOM);
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

	/**
	 * @throws Exception
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'actual_value' => $this->getActualValue(),
			'previous_value' => $this->getPreviousValue(),
			'expected_value' => $this->getExpectedValue(),
			'pending' => $this->getPending(),
			'valid' => $this->isValid(),
		]);
	}

	/**
	 * @return Array<string, mixed>
	 *
	 * @throws Exception
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
