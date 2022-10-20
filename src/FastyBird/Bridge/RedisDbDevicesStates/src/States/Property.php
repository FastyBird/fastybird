<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbDevicesStatesBridge!
 * @subpackage     States
 * @since          0.1.0
 *
 * @date           20.10.22
 */

namespace FastyBird\Bridge\RedisDbDevicesStates\States;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Plugin\RedisDb\States as RedisDbStates;
use function array_merge;
use function is_bool;

/**
 * Property state
 *
 * @package        FastyBird:RedisDbDevicesStatesBridge!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Property extends RedisDbStates\State implements DevicesStates\Property
{

	private float|bool|int|string|null $actualValue = null;

	private float|bool|int|string|null $expectedValue = null;

	private bool|string|null $pending = null;

	/** @var bool */
	private bool|null $valid = null;

	private string|null $createdAt = null;

	private string|null $updatedAt = null;

	/**
	 * @throws Exception
	 */
	public function getCreatedAt(): DateTimeInterface|null
	{
		return $this->createdAt !== null ? new DateTime($this->createdAt) : null;
	}

	public function setCreatedAt(string|null $createdAt): void
	{
		$this->createdAt = $createdAt;
	}

	/**
	 * @throws Exception
	 */
	public function getUpdatedAt(): DateTimeInterface|null
	{
		return $this->updatedAt !== null ? new DateTimeImmutable($this->updatedAt) : null;
	}

	public function setUpdatedAt(string|null $updatedAt): void
	{
		$this->updatedAt = $updatedAt;
	}

	public function getActualValue(): float|bool|int|string|null
	{
		return $this->actualValue;
	}

	public function setActualValue(float|bool|int|string|null $actual): void
	{
		$this->actualValue = $actual;
	}

	public function getExpectedValue(): float|bool|int|string|null
	{
		return $this->expectedValue;
	}

	public function setExpectedValue(float|bool|int|string|null $expected): void
	{
		$this->expectedValue = $expected;
	}

	public function isPending(): bool
	{
		return $this->valid !== null ? is_bool($this->valid) ? $this->valid : true : false;
	}

	public function getPending(): bool|string|null
	{
		return $this->pending;
	}

	public function setPending(bool|string|null $pending): void
	{
		$this->pending = $pending;
	}

	public function isValid(): bool
	{
		return $this->valid ?? false;
	}

	public function setValid(bool $valid): void
	{
		$this->valid = $valid;
	}

	public static function getCreateFields(): array
	{
		return [
			0 => 'id',
			'actualValue' => null,
			'expectedValue' => null,
			'pending' => false,
			'valid' => false,
			'createdAt' => null,
			'updatedAt' => null,
		];
	}

	public static function getUpdateFields(): array
	{
		return [
			'actualValue',
			'expectedValue',
			'pending',
			'valid',
			'updatedAt',
		];
	}

	/**
	 * @throws Exception
	 */
	public function toArray(): array
	{
		return array_merge([
			'actual_value' => $this->getActualValue(),
			'expected_value' => $this->getExpectedValue(),
			'pending' => $this->getPending(),
			'valid' => $this->isValid(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		], parent::toArray());
	}

}
