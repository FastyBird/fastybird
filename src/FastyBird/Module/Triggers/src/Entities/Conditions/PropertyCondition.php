<?php declare(strict_types = 1);

/**
 * PropertyCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\TriggersModule\Entities\Conditions;

use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\TriggersModule\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use Ramsey\Uuid;
use Throwable;

/**
 * @ORM\MappedSuperclass
 */
abstract class PropertyCondition extends Condition implements IPropertyCondition
{

	/**
	 * @var Uuid\UuidInterface
	 *
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="uuid_binary", name="condition_device", nullable=true)
	 */
	protected Uuid\UuidInterface $device;

	/**
	 * @var MetadataTypes\TriggerConditionOperatorType
	 *
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @Enum(class=MetadataTypes\TriggerConditionOperatorType::class)
	 * @ORM\Column(type="string_enum", name="condition_operator", length=15, nullable=true)
	 */
	protected $operator;

	/**
	 * @var string
	 *
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="string", name="condition_operand", length=20, nullable=true)
	 */
	protected string $operand;

	/**
	 * @param Uuid\UuidInterface $device
	 * @param MetadataTypes\TriggerConditionOperatorType $operator
	 * @param string $operand
	 * @param Entities\Triggers\IAutomaticTrigger $trigger
	 * @param Uuid\UuidInterface|null $id
	 *
	 * @throws Throwable
	 */
	public function __construct(
		Uuid\UuidInterface $device,
		MetadataTypes\TriggerConditionOperatorType $operator,
		string $operand,
		Entities\Triggers\IAutomaticTrigger $trigger,
		?Uuid\UuidInterface $id = null
	) {
		parent::__construct($trigger, $id);

		$this->device = $device;
		$this->operator = $operator;
		$this->operand = $operand;
	}

	/**
	 * {@inheritDoc}
	 */
	public function validate(string $value): bool
	{
		if ($this->operator->equalsValue(MetadataTypes\TriggerConditionOperatorType::OPERATOR_VALUE_EQUAL)) {
			return (string) $this->operand === $value;
		}

		if ($this->operator->equalsValue(MetadataTypes\TriggerConditionOperatorType::OPERATOR_VALUE_ABOVE)) {
			return (float) ((string) $this->operand) < (float) $value;
		}

		if ($this->operator->equalsValue(MetadataTypes\TriggerConditionOperatorType::OPERATOR_VALUE_BELOW)) {
			return (float) ((string) $this->operand) > (float) $value;
		}

		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'device'   => $this->getDevice()->toString(),
			'operator' => $this->getOperator()->getValue(),
			'operand'  => (string) $this->getOperand(),
		]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getOperator(): MetadataTypes\TriggerConditionOperatorType
	{
		return $this->operator;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setOperator(MetadataTypes\TriggerConditionOperatorType $operator): void
	{
		$this->operator = $operator;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getOperand()
	{
		if (MetadataTypes\ButtonPayloadType::isValidValue($this->operand)) {
			return MetadataTypes\ButtonPayloadType::get($this->operand);
		}

		if (MetadataTypes\SwitchPayloadType::isValidValue($this->operand)) {
			return MetadataTypes\SwitchPayloadType::get($this->operand);
		}

		return $this->operand;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setOperand(string $operand): void
	{
		$this->operand = $operand;
	}

}
