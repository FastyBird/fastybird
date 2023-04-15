<?php declare(strict_types = 1);

/**
 * Notification.php
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

use FastyBird\Library\Metadata\Entities;
use FastyBird\Library\Metadata\Types;
use Ramsey\Uuid;

/**
 * Notification entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Notification implements Entities\Entity, Entities\Owner
{

	use Entities\TOwner;

	private Uuid\UuidInterface $id;

	private Uuid\UuidInterface $trigger;

	private Types\TriggerNotificationType $type;

	public function __construct(
		string $id,
		string $trigger,
		string $type,
		private readonly bool $enabled,
		string|null $owner = null,
	)
	{
		$this->id = Uuid\Uuid::fromString($id);
		$this->trigger = Uuid\Uuid::fromString($trigger);
		$this->type = Types\TriggerNotificationType::get($type);
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

	public function getType(): Types\TriggerNotificationType
	{
		return $this->type;
	}

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'trigger' => $this->getTrigger()->toString(),
			'type' => $this->getType()->getValue(),
			'enabled' => $this->isEnabled(),
			'owner' => $this->getOwner()?->toString(),
		];
	}

}
