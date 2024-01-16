<?php declare(strict_types = 1);

/**
 * DeviceDynamicProperty.php
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
use function array_map;
use function array_merge;
use function is_bool;

/**
 * Device dynamic property document
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceDynamicProperty extends DeviceProperty
{

	/**
	 * @param string|array<int, string>|array<int, int>|array<int, float>|array<int, bool|string|int|float|array<int, bool|string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 * @param array<int, Uuid\UuidInterface> $children
	 */
	public function __construct(
		Uuid\UuidInterface $id,
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(
			class: Types\PropertyType::class,
			allowedValues: [Types\PropertyType::TYPE_DYNAMIC],
		)]
		private readonly Types\PropertyType $type,
		Uuid\UuidInterface $device,
		Types\PropertyCategory $category,
		string $identifier,
		string|null $name,
		Types\DataType $dataType,
		string|null $unit = null,
		string|array|null $format = null,
		float|int|string|null $invalid = null,
		int|null $scale = null,
		int|float|null $step = null,
		Uuid\UuidInterface|string|null $valueTransformer = null,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $settable = false,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $queryable = false,
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
		#[ObjectMapper\Rules\ArrayOf(
			new BootstrapObjectMapper\Rules\UuidValue(),
		)]
		private readonly array $children = [],
		Uuid\UuidInterface|null $owner = null,
		DateTimeInterface|null $createdAt = null,
		DateTimeInterface|null $updatedAt = null,
	)
	{
		parent::__construct(
			$id,
			$device,
			$category,
			$identifier,
			$name,
			$dataType,
			$unit,
			$format,
			$invalid,
			$scale,
			$step,
			$valueTransformer,
			$owner,
			$createdAt,
			$updatedAt,
		);
	}

	public function getType(): Types\PropertyType
	{
		return $this->type;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getChildren(): array
	{
		return $this->children;
	}

	public function isSettable(): bool
	{
		return $this->settable;
	}

	public function isQueryable(): bool
	{
		return $this->queryable;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function getActualValue(): bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null
	{
		try {
			return Utilities\Value::normalizeValue(
				$this->actualValue,
				$this->getDataType(),
				$this->getFormat(),
			);
		} catch (Exceptions\InvalidValue) {
			return null;
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function getPreviousValue(): bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null
	{
		try {
			return Utilities\Value::normalizeValue(
				$this->previousValue,
				$this->getDataType(),
				$this->getFormat(),
			);
		} catch (Exceptions\InvalidValue) {
			return null;
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function getExpectedValue(): bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null
	{
		try {
			return Utilities\Value::normalizeValue(
				$this->expectedValue,
				$this->getDataType(),
				$this->getFormat(),
			);
		} catch (Exceptions\InvalidValue) {
			return null;
		}
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
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'type' => $this->getType()->getValue(),
			'settable' => $this->isSettable(),
			'queryable' => $this->isQueryable(),
			'actual_value' => Utilities\Value::flattenValue($this->getActualValue()),
			'previous_value' => Utilities\Value::flattenValue($this->getPreviousValue()),
			'expected_value' => Utilities\Value::flattenValue($this->getExpectedValue()),
			'pending' => $this->getPending() instanceof DateTimeInterface
				? $this->getPending()->format(DateTimeInterface::ATOM)
				: $this->getPending(),
			'valid' => $this->isValid(),

			'children' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getChildren(),
			),
		]);
	}

}
