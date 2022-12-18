<?php declare(strict_types = 1);

/**
 * DeviceAttribute.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Entities\DevicesModule;

use FastyBird\Library\Metadata\Entities;
use Ramsey\Uuid;

/**
 * Device attribute entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceAttribute implements Entities\Entity, Entities\Owner
{

	use Entities\TOwner;

	private Uuid\UuidInterface $id;

	private Uuid\UuidInterface $device;

	public function __construct(
		string $id,
		string $device,
		private readonly string $identifier,
		private readonly string|null $name,
		private readonly string|null $content,
		string|null $owner = null,
	)
	{
		$this->id = Uuid\Uuid::fromString($id);
		$this->device = Uuid\Uuid::fromString($device);
		$this->owner = $owner !== null ? Uuid\Uuid::fromString($owner) : null;
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getContent(): string|null
	{
		return $this->content;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'device' => $this->getDevice()->toString(),
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'content' => $this->getContent(),
			'owner' => $this->getOwner()?->toString(),
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
