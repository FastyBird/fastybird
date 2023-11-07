<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           04.06.22
 */

namespace FastyBird\Library\Metadata\Entities\DevicesModule;

use FastyBird\Library\Metadata\Entities;
use FastyBird\Library\Metadata\Types;
use Nette\Utils;
use Ramsey\Uuid;
use function array_map;

/**
 * Connector entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector implements Entities\Entity, Entities\Owner
{

	use Entities\TOwner;

	private Types\ConnectorCategory $category;

	private Uuid\UuidInterface $id;

	/** @var array<Uuid\UuidInterface> */
	private array $properties;

	/** @var array<Uuid\UuidInterface> */
	private array $controls;

	/** @var array<Uuid\UuidInterface> */
	private array $devices;

	/**
	 * @param array<string>|Utils\ArrayHash<string> $properties
	 * @param array<string>|Utils\ArrayHash<string> $controls
	 * @param array<string>|Utils\ArrayHash<string> $devices
	 */
	public function __construct(
		string $id,
		private readonly string $type,
		string $category,
		private readonly string $identifier,
		private readonly string|null $name = null,
		private readonly string|null $comment = null,
		private readonly bool $enabled = false,
		array|Utils\ArrayHash $properties = [],
		array|Utils\ArrayHash $controls = [],
		array|Utils\ArrayHash $devices = [],
		string|null $owner = null,
	)
	{
		$this->id = Uuid\Uuid::fromString($id);
		$this->category = Types\ConnectorCategory::get($category);
		$this->properties = array_map(
			static fn (string $id): Uuid\UuidInterface => Uuid\Uuid::fromString($id),
			(array) $properties,
		);
		$this->controls = array_map(
			static fn (string $id): Uuid\UuidInterface => Uuid\Uuid::fromString($id),
			(array) $controls,
		);
		$this->devices = array_map(
			static fn (string $id): Uuid\UuidInterface => Uuid\Uuid::fromString($id),
			(array) $devices,
		);
		$this->owner = $owner !== null ? Uuid\Uuid::fromString($owner) : null;
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getCategory(): Types\ConnectorCategory
	{
		return $this->category;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getName(): string|null
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

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getProperties(): array
	{
		return $this->properties;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getControls(): array
	{
		return $this->controls;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getDevices(): array
	{
		return $this->devices;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'type' => $this->getType(),
			'category' => $this->getCategory()->getValue(),
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'comment' => $this->getComment(),
			'enabled' => $this->isEnabled(),
			'properties' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getProperties(),
			),
			'controls' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getControls(),
			),
			'devices' => array_map(static fn (Uuid\UuidInterface $id): string => $id->toString(), $this->getDevices()),
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
