<?php declare(strict_types = 1);

/**
 * Trigger.php
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

namespace FastyBird\Metadata\Entities\TriggersModule;

use FastyBird\Metadata\Entities;
use FastyBird\Metadata\Types;
use Ramsey\Uuid;

/**
 * Trigger entity
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Trigger implements Entities\Entity, Entities\Owner
{

	use Entities\TOwner;

	private Uuid\UuidInterface $id;

	private Types\TriggerType $type;

	private bool|null $triggered;

	public function __construct(
		string $id,
		string $type,
		private readonly string $name,
		private readonly bool $enabled,
		private readonly string|null $comment = null,
		bool|null $isTriggered = null,
		string|null $owner = null,
	)
	{
		$this->id = Uuid\Uuid::fromString($id);
		$this->type = Types\TriggerType::get($type);
		$this->triggered = $isTriggered;
		$this->owner = $owner !== null ? Uuid\Uuid::fromString($owner) : null;
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getType(): Types\TriggerType
	{
		return $this->type;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getComment(): string|null
	{
		return $this->comment;
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
			'type' => $this->getType()->getValue(),
			'name' => $this->getName(),
			'comment' => $this->getComment(),
			'enabled' => $this->isEnabled(),
			'is_triggered' => $this->isTriggered(),
			'owner' => $this->getOwner()?->toString(),
		];
	}

}
