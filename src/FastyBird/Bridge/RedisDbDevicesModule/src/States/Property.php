<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     States
 * @since          0.1.0
 *
 * @date           20.10.22
 */

namespace FastyBird\Bridge\RedisDbDevicesModule\States;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Plugin\RedisDb\States as RedisDbStates;
use Nette\Utils;
use function array_merge;
use function is_bool;
use function is_string;

/**
 * Property state
 *
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Property extends RedisDbStates\State implements DevicesStates\Property
{

	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	private bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $actualValue = null;
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	private bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $expectedValue = null;

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

	public function setCreatedAt(string|null $createdAt = null): void
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

	public function setUpdatedAt(string|null $updatedAt = null): void
	{
		$this->updatedAt = $updatedAt;
	}
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getActualValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		return $this->actualValue;
	}

	public function setActualValue(
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $actual,
	): void
	{
		$this->actualValue = $actual;
	}
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getExpectedValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		return $this->expectedValue;
	}

	public function setExpectedValue(
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $expected,
	): void
	{
		$this->expectedValue = $expected;
	}

	public function isPending(): bool
	{
		return $this->pending !== null ? is_bool($this->pending) ? $this->pending : true : false;
	}

	public function getPending(): bool|DateTimeInterface|null
	{
		if (is_string($this->pending)) {
			return Utils\DateTime::createFromFormat(DateTimeInterface::ATOM, $this->pending);
		}

		return $this->pending;
	}

	public function setPending(bool|string|null $pending = null): void
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
			DevicesStates\Property::ACTUAL_VALUE_KEY => null,
			DevicesStates\Property::EXPECTED_VALUE_KEY => null,
			DevicesStates\Property::PENDING_KEY => false,
			DevicesStates\Property::VALID_KEY => false,
			'createdAt' => null,
			'updatedAt' => null,
		];
	}

	public static function getUpdateFields(): array
	{
		return [
			DevicesStates\Property::ACTUAL_VALUE_KEY,
			DevicesStates\Property::EXPECTED_VALUE_KEY,
			DevicesStates\Property::PENDING_KEY,
			DevicesStates\Property::VALID_KEY,
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
			'pending' => $this->getPending() instanceof DateTimeInterface ? $this->getPending()->format(
				DateTimeInterface::ATOM,
			) : $this->getPending(),
			'valid' => $this->isValid(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		], parent::toArray());
	}

}
