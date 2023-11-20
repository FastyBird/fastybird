<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     States
 * @since          1.0.0
 *
 * @date           20.10.22
 */

namespace FastyBird\Bridge\RedisDbDevicesModule\States;

use DateTimeInterface;
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Plugin\RedisDb\States as RedisDbStates;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;
use function is_bool;

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

	public function __construct(
		Uuid\UuidInterface $id,
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
		parent::__construct($id);
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

	public static function getCreateFields(): array
	{
		return [
			0 => 'id',
			DevicesStates\Property::ACTUAL_VALUE_FIELD => null,
			DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
			DevicesStates\Property::PENDING_FIELD => false,
			DevicesStates\Property::VALID_FIELD => false,
			self::CREATED_AT_FIELD => null,
			self::UPDATED_AT_FIELD => null,
		];
	}

	public static function getUpdateFields(): array
	{
		return [
			DevicesStates\Property::ACTUAL_VALUE_FIELD,
			DevicesStates\Property::EXPECTED_VALUE_FIELD,
			DevicesStates\Property::PENDING_FIELD,
			DevicesStates\Property::VALID_FIELD,
			self::UPDATED_AT_FIELD,
		];
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'actual_value' => MetadataUtilities\ValueHelper::flattenValue($this->getActualValue()),
			'expected_value' => MetadataUtilities\ValueHelper::flattenValue($this->getExpectedValue()),
			'pending' => $this->getPending() instanceof DateTimeInterface
				? $this->getPending()->format(DateTimeInterface::ATOM)
				: $this->getPending(),
			'valid' => $this->isValid(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		]);
	}

}
