<?php declare(strict_types = 1);

/**
 * DummyCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           12.11.23
 */

namespace FastyBird\Library\Metadata\Documents\TriggersModule;

use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Metadata\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Dummy condition document
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DummyCondition extends Condition
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $trigger,
		string $type,
		bool $enabled,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		#[ObjectMapper\Modifiers\FieldName('watch_item')]
		private readonly Uuid\UuidInterface $watchItem,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $operand,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: Types\TriggerConditionOperator::class),
			new ObjectMapper\Rules\InstanceOfValue(type: Types\TriggerConditionOperator::class),
		])]
		private readonly Types\TriggerConditionOperator $operator,
		bool|null $isFulfilled = null,
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $type, $enabled, $isFulfilled, $owner);
	}

	public function getWatchItem(): Uuid\UuidInterface
	{
		return $this->watchItem;
	}

	public function getOperand(): string
	{
		return $this->operand;
	}

	public function getOperator(): Types\TriggerConditionOperator
	{
		return $this->operator;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'watch_item' => $this->getWatchItem()->toString(),
			'operand' => $this->getOperand(),
			'operator' => $this->getOperator()->value,
		]);
	}

}
