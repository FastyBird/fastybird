<?php declare(strict_types = 1);

/**
 * Action.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Library\Metadata\Entities\TriggersModule;

use FastyBird\Library\Metadata\Entities;
use FastyBird\Library\Metadata\Types;
use Ramsey\Uuid;

/**
 * Action entity
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Action implements Entities\Entity, Entities\Owner
{

	use Entities\TOwner;

	private Uuid\UuidInterface $id;

	private Uuid\UuidInterface $trigger;

	private Types\TriggerActionType $type;

	private bool|null $triggered;

	public function __construct(
		string $id,
		string $trigger,
		string $type,
		private readonly bool $enabled,
		bool|null $isTriggered = null,
		string|null $owner = null,
	)
	{
		$this->id = Uuid\Uuid::fromString($id);
		$this->trigger = Uuid\Uuid::fromString($trigger);
		$this->type = Types\TriggerActionType::get($type);
		$this->triggered = $isTriggered;
		$this->owner = $owner !== null ? Uuid\Uuid::fromString($owner) : null;
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getTrigger(): Uuid\UuidInterface
	{
		return $this->trigger;
	}

	public function getType(): Types\TriggerActionType
	{
		return $this->type;
	}

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	public function isTriggered(): bool|null
	{
		return $this->triggered;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'trigger' => $this->getTrigger()->toString(),
			'type' => $this->getType()->getValue(),
			'enabled' => $this->isEnabled(),
			'is_triggered' => $this->isTriggered(),
			'owner' => $this->getOwner()?->toString(),
		];
	}

}
