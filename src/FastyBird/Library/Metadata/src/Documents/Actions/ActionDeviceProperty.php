<?php declare(strict_types = 1);

/**
 * ActionDeviceProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Library\Metadata\Documents\Actions;

use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Documents;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;
use function sprintf;

/**
 * Device property action document
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ActionDeviceProperty implements Documents\Document
{

	public function __construct(
		#[ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\PropertyAction::class)]
		private readonly Types\PropertyAction $action,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $device,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $property,
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

	public function getAction(): Types\PropertyAction
	{
		return $this->action;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getActualValue(): float|bool|int|string|null
	{
		if (!$this->getAction()->equalsValue(Types\PropertyAction::SET)) {
			throw new Exceptions\InvalidState(
				sprintf('Actual value is available only for action: %s', Types\PropertyAction::SET),
			);
		}

		return $this->actualValue;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getExpectedValue(): float|bool|int|string|null
	{
		if (!$this->getAction()->equalsValue(Types\PropertyAction::SET)) {
			throw new Exceptions\InvalidState(
				sprintf('Expected value is available only for action: %s', Types\PropertyAction::SET),
			);
		}

		return $this->expectedValue;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		$data = [
			'action' => $this->getAction()->getValue(),
			'device' => $this->getDevice()->toString(),
			'property' => $this->getProperty()->toString(),
		];

		if ($this->getAction()->equalsValue(Types\PropertyAction::SET)) {
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
		}

		return $data;
	}

}
