<?php declare(strict_types = 1);

/**
 * PropertyValues.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           23.01.24
 */

namespace FastyBird\Library\Metadata\Documents\DevicesModule;

use DateTimeInterface;
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Metadata\Documents;
use FastyBird\Library\Metadata\Types;
use FastyBird\Library\Metadata\Utilities;
use Orisai\ObjectMapper;

/**
 * Property value document
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class PropertyValues implements Documents\Document
{

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\ButtonPayload::class),
			new ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\SwitchPayload::class),
			new ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\CoverPayload::class),
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('actual_value')]
		private readonly bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null $actualValue,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\ButtonPayload::class),
			new ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\SwitchPayload::class),
			new ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\CoverPayload::class),
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('expected_value')]
		private readonly bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null $expectedValue,
	)
	{
	}

	public function getActualValue(): bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null
	{
		return $this->actualValue;
	}

	public function getExpectedValue(): bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null
	{
		return $this->expectedValue;
	}

	public function toArray(): array
	{
		return [
			'actual_value' => Utilities\Value::flattenValue($this->getActualValue()),
			'expected_value' => Utilities\Value::flattenValue($this->getExpectedValue()),
		];
	}

}
