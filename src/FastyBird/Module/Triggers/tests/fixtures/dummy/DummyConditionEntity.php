<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Fixtures\Dummy;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Application\Doctrine\Mapping as ApplicationMapping;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Entities;
use IPub\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use Ramsey\Uuid;
use function array_merge;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class DummyConditionEntity extends Entities\Conditions\Condition
{

	public const TYPE = 'dummy';

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\Column(name: 'condition_watch_item', type: Uuid\Doctrine\UuidBinaryType::NAME, nullable: true)]
	private Uuid\UuidInterface $watchItem;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(
		name: 'condition_operator',
		type: 'string',
		length: 15,
		nullable: true,
		enumType: MetadataTypes\TriggerConditionOperator::class,
	)]
	private MetadataTypes\TriggerConditionOperator $operator;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'condition_operand', type: 'string', length: 20, nullable: true)]
	private string $operand;

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function setWatchItem(Uuid\UuidInterface $watchItem): void
	{
		$this->watchItem = $watchItem;
	}

	public function getWatchItem(): Uuid\UuidInterface
	{
		return $this->watchItem;
	}

	public function setOperator(MetadataTypes\TriggerConditionOperator $operator): void
	{
		$this->operator = $operator;
	}

	public function getOperator(): MetadataTypes\TriggerConditionOperator
	{
		return $this->operator;
	}

	public function setOperand(string $operand): void
	{
		$this->operand = $operand;
	}

	public function getOperand(): string
	{
		return $this->operand;
	}

	public function validate(string $value): bool
	{
		if ($this->operator === MetadataTypes\TriggerConditionOperator::EQUAL) {
			return $this->operand === $value;
		}

		if ($this->operator === MetadataTypes\TriggerConditionOperator::ABOVE) {
			return (float) ($this->operand) < (float) $value;
		}

		if ($this->operator === MetadataTypes\TriggerConditionOperator::BELOW) {
			return (float) ($this->operand) > (float) $value;
		}

		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'watch_item' => $this->getWatchItem()->toString(),
			'operator' => $this->getOperator()->value,
			'operand' => $this->getOperand(),
		]);
	}

}
