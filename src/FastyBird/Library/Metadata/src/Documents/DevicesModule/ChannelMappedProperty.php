<?php declare(strict_types = 1);

/**
 * ChannelMappedProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Documents\DevicesModule;

use DateTimeInterface;
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Types;
use FastyBird\Library\Metadata\Utilities;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;
use function is_bool;

/**
 * Channel mapped property document
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelMappedProperty extends ChannelProperty
{

	/**
	 * @param string|array<int, string>|array<int, int>|array<int, float>|array<int, bool|string|int|float|array<int, bool|string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 */
	public function __construct(
		Uuid\UuidInterface $id,
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(
			class: Types\PropertyType::class,
			allowedValues: [Types\PropertyType::TYPE_MAPPED],
		)]
		private readonly Types\PropertyType $type,
		#[BootstrapObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $channel,
		#[BootstrapObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $parent,
		Types\PropertyCategory $category,
		string $identifier,
		string|null $name,
		Types\DataType $dataType,
		string|null $unit = null,
		string|array|null $format = null,
		float|int|string|null $invalid = null,
		int|null $scale = null,
		int|float|null $step = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly bool|null $settable = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly bool|null $queryable = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('actual_value')]
		private readonly bool|float|int|string|null $actualValue = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('previous_value')]
		private readonly bool|float|int|string|null $previousValue = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('expected_value')]
		private readonly bool|float|int|string|null $expectedValue = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
		])]
		private readonly bool|DateTimeInterface $pending = false,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $valid = false,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly bool|float|int|string|null $value = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly bool|float|int|string|null $default = null,
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct(
			$id,
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

	public function getType(): Types\PropertyType
	{
		return $this->type;
	}

	public function getChannel(): Uuid\UuidInterface
	{
		return $this->channel;
	}

	public function getParent(): Uuid\UuidInterface
	{
		return $this->parent;
	}

	public function isSettable(): bool
	{
		return $this->settable ?? false;
	}

	public function isQueryable(): bool
	{
		return $this->queryable ?? false;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function getActualValue(): bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null
	{
		return Utilities\ValueHelper::normalizeValue(
			$this->getDataType(),
			$this->actualValue,
			$this->getFormat(),
			$this->getInvalid(),
		);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function getPreviousValue(): bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null
	{
		return Utilities\ValueHelper::normalizeValue(
			$this->getDataType(),
			$this->previousValue,
			$this->getFormat(),
			$this->getInvalid(),
		);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function getExpectedValue(): bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null
	{
		return Utilities\ValueHelper::normalizeValue(
			$this->getDataType(),
			$this->expectedValue,
			$this->getFormat(),
			$this->getInvalid(),
		);
	}

	public function getPending(): bool|DateTimeInterface
	{
		return $this->pending;
	}

	public function isPending(): bool
	{
		return is_bool($this->pending) ? $this->pending : true;
	}

	public function isValid(): bool
	{
		return $this->valid;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function getValue(): bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null
	{
		return Utilities\ValueHelper::normalizeValue(
			$this->getDataType(),
			$this->value,
			$this->getFormat(),
			$this->getInvalid(),
		);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function getDefault(): bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null
	{
		return Utilities\ValueHelper::normalizeValue(
			$this->getDataType(),
			$this->default,
			$this->getFormat(),
			$this->getInvalid(),
		);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'type' => $this->getType()->getValue(),
			'settable' => $this->isSettable(),
			'queryable' => $this->isQueryable(),
			'actual_value' => Utilities\ValueHelper::flattenValue($this->getActualValue()),
			'previous_value' => Utilities\ValueHelper::flattenValue($this->getPreviousValue()),
			'expected_value' => Utilities\ValueHelper::flattenValue($this->getExpectedValue()),
			'pending' => $this->getPending() instanceof DateTimeInterface
				? $this->getPending()->format(DateTimeInterface::ATOM)
				: $this->getPending(),
			'valid' => $this->isValid(),
			'value' => Utilities\ValueHelper::flattenValue($this->getValue()),
			'default' => Utilities\ValueHelper::flattenValue($this->getDefault()),

			'channel' => $this->getChannel()->toString(),
			'parent' => $this->getParent()->toString(),
		]);
	}

}
