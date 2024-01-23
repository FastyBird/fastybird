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

namespace FastyBird\Library\Metadata\Documents\Actions;

use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Documents;
use Orisai\ObjectMapper;
use function array_merge;

/**
 * Property set action value document
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
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('actual_value')]
		private readonly bool|float|int|string|null $actualValue = Metadata\Constants::VALUE_NOT_SET,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('expected_value')]
		private readonly bool|float|int|string|null $expectedValue = Metadata\Constants::VALUE_NOT_SET,
	)
	{
	}

	public function getActualValue(): float|bool|int|string|null
	{
		return $this->actualValue;
	}

	public function getExpectedValue(): float|bool|int|string|null
	{
		return $this->expectedValue;
	}

	public function toArray(): array
	{
		$data = [];

		if ($this->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
			$data = array_merge($data, [
				'actual_value' => $this->getActualValue(),
			]);
		}

		if ($this->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
			$data = array_merge($data, [
				'expected_value' => $this->getExpectedValue(),
			]);
		}

		return $data;
	}

}
