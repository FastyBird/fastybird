<?php declare(strict_types = 1);

/**
 * ActionTriggerEntity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Library\Metadata\Entities\Actions;

use FastyBird\Library\Metadata\Entities;
use FastyBird\Library\Metadata\Types;
use Ramsey\Uuid;

/**
 * Trigger control action entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ActionTriggerControl implements Entities\Entity
{

	private Types\TriggerAction $action;

	private Uuid\UuidInterface $trigger;

	private Uuid\UuidInterface $control;

	private string|int|bool|float|null $expectedValue;

	public function __construct(
		string $action,
		string $trigger,
		string $control,
		float|bool|int|string|null $expectedValue = null,
	)
	{
		$this->action = Types\TriggerAction::get($action);

		$this->trigger = Uuid\Uuid::fromString($trigger);
		$this->control = Uuid\Uuid::fromString($control);

		$this->expectedValue = $expectedValue;
	}

	public function getAction(): Types\TriggerAction
	{
		return $this->action;
	}

	public function getTrigger(): Uuid\UuidInterface
	{
		return $this->trigger;
	}

	public function getControl(): Uuid\UuidInterface
	{
		return $this->control;
	}

	public function getExpectedValue(): float|bool|int|string|null
	{
		return $this->expectedValue;
	}

	public function toArray(): array
	{
		return [
			'action' => $this->getAction()->getValue(),
			'trigger' => $this->getTrigger()->toString(),
			'control' => $this->getControl()->toString(),
			'expected_value' => $this->getExpectedValue(),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
