<?php declare(strict_types = 1);

/**
 * ChannelPropertyCondition.php
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
use FastyBird\Module\Triggers\Documents as TriggersDocuments;
use FastyBird\Module\Triggers\Types as TriggersTypes;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Channel property condition document
 *
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[DOC\Document(entity: Entities\Conditions\ChannelPropertyCondition::class)]
#[DOC\DiscriminatorEntry(name: Entities\Conditions\ChannelPropertyCondition::TYPE)]
final class ChannelPropertyCondition extends TriggersDocuments\Conditions\Condition
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $trigger,
		bool $enabled,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $device,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $channel,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $property,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $operand,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: TriggersTypes\ConditionOperator::class),
			new ObjectMapper\Rules\InstanceOfValue(type: TriggersTypes\ConditionOperator::class),
		])]
		private readonly TriggersTypes\ConditionOperator $operator,
		bool|null $isFulfilled = null,
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $enabled, $isFulfilled, $owner);
	}

	public static function getType(): string
	{
		return Entities\Conditions\ChannelPropertyCondition::TYPE;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getChannel(): Uuid\UuidInterface
	{
		return $this->channel;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	public function getOperand(): string
	{
		return $this->operand;
	}

	public function getOperator(): TriggersTypes\ConditionOperator
	{
		return $this->operator;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'device' => $this->getDevice()->toString(),
			'channel' => $this->getChannel()->toString(),
			'property' => $this->getProperty()->toString(),
			'operand' => $this->getOperand(),
			'operator' => $this->getOperator()->value,
		]);
	}

}
