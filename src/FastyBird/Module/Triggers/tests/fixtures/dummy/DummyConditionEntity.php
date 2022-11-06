<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Fixtures\Dummy;

use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use Ramsey\Uuid;
use function array_merge;
use function strval;

/**
 * @ORM\Entity
 */
class DummyConditionEntity extends Entities\Conditions\Condition
{

	public const CONDITION_TYPE = 'dummy';

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="uuid_binary", name="condition_watch_item", nullable=true)
	 */
	private Uuid\UuidInterface $watchItem;

	/**
	 * @var MetadataTypes\TriggerConditionOperator
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
	 *
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @Enum(class=MetadataTypes\TriggerConditionOperator::class)
	 * @ORM\Column(type="string_enum", name="condition_operator", length=15, nullable=true)
	 */
	private $operator;

	/**
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="string", name="condition_operand", length=20, nullable=true)
	 */
	private string $operand;

	public function getType(): string
	{
		return 'dummy';
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

	public function getDiscriminatorName(): string
	{
		return 'dummy';
	}

	public function validate(string $value): bool
	{
		if ($this->operator->equalsValue(MetadataTypes\TriggerConditionOperator::OPERATOR_VALUE_EQUAL)) {
			return $this->operand === $value;
		}

		if ($this->operator->equalsValue(MetadataTypes\TriggerConditionOperator::OPERATOR_VALUE_ABOVE)) {
			return (float) ($this->operand) < (float) $value;
		}

		if ($this->operator->equalsValue(MetadataTypes\TriggerConditionOperator::OPERATOR_VALUE_BELOW)) {
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
			'operator' => strval($this->getOperator()->getValue()),
			'operand' => $this->getOperand(),
		]);
	}

}
