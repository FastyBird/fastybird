<?php declare(strict_types = 1);

/**
 * ChannelPropertyCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Entities\TriggersModule;

use FastyBird\Library\Metadata\Types;
use Ramsey\Uuid;
use function array_merge;

/**
 * Channel property condition entity
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelPropertyCondition extends Condition
{

	private Uuid\UuidInterface $device;

	private Uuid\UuidInterface $channel;

	private Uuid\UuidInterface $property;

	private Types\TriggerConditionOperator $operator;

	public function __construct(
		string $id,
		string $trigger,
		string $type,
		bool $enabled,
		string $device,
		string $channel,
		string $property,
		private readonly string $operand,
		string $operator,
		bool|null $isFulfilled = null,
		string|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $type, $enabled, $isFulfilled, $owner);

		$this->device = Uuid\Uuid::fromString($device);
		$this->channel = Uuid\Uuid::fromString($channel);
		$this->property = Uuid\Uuid::fromString($property);
		$this->operator = Types\TriggerConditionOperator::get($operator);
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

	public function getOperator(): Types\TriggerConditionOperator
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
			'operator' => $this->getOperator()->getValue(),
		]);
	}

	/**
	 * @return Array<string, mixed>
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
