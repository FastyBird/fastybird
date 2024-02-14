<?php declare(strict_types = 1);

/**
 * PropertyCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Automator\DevicesModule\Entities\Conditions;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use FastyBird\Module\Triggers\Types as TriggersTypes;
use IPub\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use Ramsey\Uuid;
use function array_merge;

#[ORM\MappedSuperclass]
abstract class PropertyCondition extends TriggersEntities\Conditions\Condition
{

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\Column(name: 'condition_device', type: Uuid\Doctrine\UuidBinaryType::NAME, nullable: true)]
	protected Uuid\UuidInterface $device;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(
		name: 'condition_operator',
		type: 'string',
		length: 15,
		nullable: true,
		enumType: TriggersTypes\ConditionOperator::class,
	)]
	protected TriggersTypes\ConditionOperator $operator;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'condition_operand', type: 'string', nullable: true, length: 20)]
	protected string $operand;

	public function __construct(
		Uuid\UuidInterface $device,
		TriggersTypes\ConditionOperator $operator,
		string $operand,
		TriggersEntities\Triggers\Automatic $trigger,
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

	public function getOperator(): TriggersTypes\ConditionOperator
	{
		return $this->operator;
	}

	public function setOperator(TriggersTypes\ConditionOperator $operator): void
	{
		$this->operator = $operator;
	}

	public function getOperand(): string|MetadataTypes\Payloads\Payload
	{
		if (MetadataTypes\Payloads\Button::isValidValue($this->operand)) {
			return MetadataTypes\Payloads\Button::get($this->operand);
		}

		if (MetadataTypes\Payloads\Switcher::isValidValue($this->operand)) {
			return MetadataTypes\Payloads\Switcher::get($this->operand);
		}

		if (MetadataTypes\Payloads\Cover::isValidValue($this->operand)) {
			return MetadataTypes\Payloads\Cover::get($this->operand);
		}

		return $this->operand;
	}

	public function setOperand(string $operand): void
	{
		$this->operand = $operand;
	}

	public function validate(string $value): bool
	{
		if ($this->operator === TriggersTypes\ConditionOperator::EQUAL) {
			return $this->operand === $value;
		}

		if ($this->operator === TriggersTypes\ConditionOperator::ABOVE) {
			return (float) ($this->operand) < (float) $value;
		}

		return (float) ($this->operand) > (float) $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'device' => $this->getDevice()->toString(),
			'operator' => $this->getOperator()->value,
			'operand' => (string) $this->getOperand(),
		]);
	}

}
