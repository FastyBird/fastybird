<?php declare(strict_types = 1);

/**
 * PropertyCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Automator\DevicesModule\Entities\Conditions;

use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use Ramsey\Uuid;
use function array_merge;

/**
 * @ORM\MappedSuperclass
 */
abstract class PropertyCondition extends TriggersEntities\Conditions\Condition
{

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="uuid_binary", name="condition_device", nullable=true)
	 */
	protected Uuid\UuidInterface $device;

	/**
	 * @var MetadataTypes\TriggerConditionOperator
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
	 *
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @Enum(class=MetadataTypes\TriggerConditionOperator::class)
	 * @ORM\Column(type="string_enum", name="condition_operator", length=15, nullable=true)
	 */
	protected $operator;

	/**
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="string", name="condition_operand", length=20, nullable=true)
	 */
	protected string $operand;

	public function __construct(
		Uuid\UuidInterface $device,
		MetadataTypes\TriggerConditionOperator $operator,
		string $operand,
		TriggersEntities\Triggers\AutomaticTrigger $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($trigger, $id);

		$this->device = $device;
		$this->operator = $operator;
		$this->operand = $operand;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getOperator(): MetadataTypes\TriggerConditionOperator
	{
		return $this->operator;
	}

	public function setOperator(MetadataTypes\TriggerConditionOperator $operator): void
	{
		$this->operator = $operator;
	}

	public function getOperand(): string|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload
	{
		if (MetadataTypes\ButtonPayload::isValidValue($this->operand)) {
			return MetadataTypes\ButtonPayload::get($this->operand);
		}

		if (MetadataTypes\SwitchPayload::isValidValue($this->operand)) {
			return MetadataTypes\SwitchPayload::get($this->operand);
		}

		if (MetadataTypes\CoverPayload::isValidValue($this->operand)) {
			return MetadataTypes\CoverPayload::get($this->operand);
		}

		return $this->operand;
	}

	public function setOperand(string $operand): void
	{
		$this->operand = $operand;
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
			'device' => $this->getDevice()->toString(),
			'operator' => $this->getOperator()->getValue(),
			'operand' => (string) $this->getOperand(),
		]);
	}

}
