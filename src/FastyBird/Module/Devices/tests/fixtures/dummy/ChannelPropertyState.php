<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Fixtures\Dummy;

use DateTimeInterface;
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices\States;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function is_bool;

class ChannelPropertyState implements States\ChannelProperty
{

	public const CREATED_AT_FIELD = 'createdAt';

	public const UPDATED_AT_FIELD = 'updatedAt';

	public function __construct(
		#[BootstrapObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: MetadataTypes\ButtonPayload::class),
			new BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: MetadataTypes\SwitchPayload::class),
			new BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: MetadataTypes\CoverPayload::class),
			new ObjectMapper\Rules\ObjectValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName(self::ACTUAL_VALUE_FIELD)]
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		private readonly bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $actualValue = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: MetadataTypes\ButtonPayload::class),
			new BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: MetadataTypes\SwitchPayload::class),
			new BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: MetadataTypes\CoverPayload::class),
			new ObjectMapper\Rules\ObjectValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName(self::EXPECTED_VALUE_FIELD)]
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		private readonly bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $expectedValue = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\ObjectValue(),
			new ObjectMapper\Rules\BoolValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(self::PENDING_FIELD)]
		private readonly bool|DateTimeInterface $pending = false,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		#[ObjectMapper\Modifiers\FieldName(self::VALID_FIELD)]
		private readonly bool $valid = false,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\ObjectValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName(self::CREATED_AT_FIELD)]
		private readonly DateTimeInterface|null $createdAt = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\ObjectValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName(self::UPDATED_AT_FIELD)]
		private readonly DateTimeInterface|null $updatedAt = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getCreatedAt(): DateTimeInterface|null
	{
		return $this->createdAt;
	}

	public function getUpdatedAt(): DateTimeInterface|null
	{
		return $this->updatedAt;
	}

	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getActualValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		return $this->actualValue;
	}

	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getExpectedValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		return $this->expectedValue;
	}

	public function isPending(): bool
	{
		return is_bool($this->pending) ? $this->pending : true;
	}

	public function getPending(): bool|DateTimeInterface
	{
		return $this->pending;
	}

	public function isValid(): bool
	{
		return $this->valid;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'actual_value' => MetadataUtilities\Value::flattenValue($this->getActualValue()),
			'expected_value' => MetadataUtilities\Value::flattenValue($this->getExpectedValue()),
			'pending' => $this->getPending() instanceof DateTimeInterface
				? $this->getPending()->format(DateTimeInterface::ATOM)
				: $this->getPending(),
			'valid' => $this->isValid(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

}
