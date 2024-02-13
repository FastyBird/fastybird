<?php declare(strict_types = 1);

/**
 * DevicePropertyCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Automator\DevicesModule\Documents\Conditions;

use FastyBird\Automator\DevicesModule\Entities;
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Metadata\Documents\Mapping as DOC;
use FastyBird\Library\Metadata\Types;
use FastyBird\Module\Triggers\Documents as TriggersDocuments;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Device property condition document
 *
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[DOC\Document(entity: Entities\Conditions\DevicePropertyCondition::class)]
#[DOC\DiscriminatorEntry(name: Entities\Conditions\DevicePropertyCondition::TYPE)]
final class DevicePropertyCondition extends TriggersDocuments\Conditions\Condition
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $trigger,
		bool $enabled,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $device,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $property,
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
		parent::__construct($id, $trigger, $enabled, $isFulfilled, $owner);
	}

	public static function getType(): string
	{
		return Entities\Conditions\DevicePropertyCondition::TYPE;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
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
			'device' => $this->getDevice()->toString(),
			'property' => $this->getProperty()->toString(),
			'operand' => $this->getOperand(),
			'operator' => $this->getOperator()->value,
		]);
	}

}
