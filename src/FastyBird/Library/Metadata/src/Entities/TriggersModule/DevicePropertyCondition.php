<?php declare(strict_types = 1);

/**
 * DevicePropertyCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Entities\TriggersModule;

use FastyBird\Library\Metadata\Types;
use Ramsey\Uuid;
use function array_merge;

/**
 * Device property condition entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicePropertyCondition extends Condition
{

	private Uuid\UuidInterface $device;

	private Uuid\UuidInterface $property;

	private Types\TriggerConditionOperator $operator;

	public function __construct(
		string $id,
		string $trigger,
		string $type,
		bool $enabled,
		string $device,
		string $property,
		private string $operand,
		string $operator,
		bool|null $isFulfilled = null,
		string|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $type, $enabled, $isFulfilled, $owner);

		$this->device = Uuid\Uuid::fromString($device);
		$this->property = Uuid\Uuid::fromString($property);
		$this->operator = Types\TriggerConditionOperator::get($operator);
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
			'operator' => $this->getOperator()->getValue(),
		]);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
