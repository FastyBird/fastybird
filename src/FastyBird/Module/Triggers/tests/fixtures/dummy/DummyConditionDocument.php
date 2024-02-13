<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Fixtures\Dummy;

use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Metadata\Documents\Mapping as DOC;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Documents;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

#[DOC\Document(entity: DummyConditionEntity::class)]
#[DOC\DiscriminatorEntry(name: DummyConditionEntity::TYPE)]
final class DummyConditionDocument extends Documents\Conditions\Condition
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
			new ObjectMapper\Rules\BackedEnumValue(class: MetadataTypes\TriggerConditionOperator::class),
			new ObjectMapper\Rules\InstanceOfValue(type: MetadataTypes\TriggerConditionOperator::class),
		])]
		private readonly MetadataTypes\TriggerConditionOperator $operator,
		bool|null $isFulfilled = null,
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $enabled, $isFulfilled, $owner);
	}

	public static function getType(): string
	{
		return DummyConditionEntity::TYPE;
	}

	public function getWatchItem(): Uuid\UuidInterface
	{
		return $this->watchItem;
	}

	public function getOperand(): string
	{
		return $this->operand;
	}

	public function getOperator(): MetadataTypes\TriggerConditionOperator
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
