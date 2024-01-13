<?php declare(strict_types = 1);

namespace FastyBird\Addon\ThermostatDevice\Tests\Fixtures\Dummy;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette\Utils;
use Ramsey\Uuid;
use function is_bool;
use function is_string;

class DummyChannelPropertyState implements DevicesStates\ChannelProperty
{

	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	private bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $actualValue = null;
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	private bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $expectedValue = null;

	private bool|string $pending = false;

	/** @var bool */
	private bool|null $valid = null;

	private string|null $createdAt = null;

	private string|null $updatedAt = null;

	public function __construct(private readonly Uuid\UuidInterface $id)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

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
		return is_bool($this->pending) ? $this->pending : true;
	}

	public function getPending(): bool|DateTimeInterface
	{
		if (is_string($this->pending)) {
			return Utils\DateTime::createFromFormat(DateTimeInterface::ATOM, $this->pending);
		}

		return $this->pending;
	}

	public function setPending(bool|string $pending = false): void
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

	/**
	 * @throws Exception
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'actual_value' => MetadataUtilities\ValueHelper::flattenValue($this->getActualValue()),
			'expected_value' => MetadataUtilities\ValueHelper::flattenValue($this->getExpectedValue()),
			'pending' => $this->getPending() instanceof DateTimeInterface ? $this->getPending()->format(
				DateTimeInterface::ATOM,
			) : $this->getPending(),
			'valid' => $this->isValid(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

}
