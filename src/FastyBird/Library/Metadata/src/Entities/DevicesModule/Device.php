<?php declare(strict_types = 1);

/**
 * Device.php
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
 * Device entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device implements Entities\Entity, Entities\Owner
{

	use Entities\TOwner;

	private Uuid\UuidInterface $id;

	private Types\DeviceCategory $category;

	private Uuid\UuidInterface $connector;

	/** @var array<Uuid\UuidInterface> */
	private array $parents;

	/** @var array<Uuid\UuidInterface> */
	private array $children;

	/** @var array<Uuid\UuidInterface> */
	private array $properties;

	/** @var array<Uuid\UuidInterface> */
	private array $controls;

	/** @var array<Uuid\UuidInterface> */
	private array $channels;

	/**
	 * @param array<string>|Utils\ArrayHash<string> $parents
	 * @param array<string>|Utils\ArrayHash<string> $children
	 * @param array<string>|Utils\ArrayHash<string> $properties
	 * @param array<string>|Utils\ArrayHash<string> $controls
	 * @param array<string>|Utils\ArrayHash<string> $channels
	 */
	public function __construct(
		string $id,
		private readonly string $type,
		string $category,
		private readonly string $identifier,
		string $connector,
		array|Utils\ArrayHash $parents,
		array|Utils\ArrayHash $children,
		private readonly string|null $name = null,
		private readonly string|null $comment = null,
		array|Utils\ArrayHash $properties = [],
		array|Utils\ArrayHash $controls = [],
		array|Utils\ArrayHash $channels = [],
		string|null $owner = null,
	)
	{
		$this->id = Uuid\Uuid::fromString($id);
		$this->category = Types\DeviceCategory::get($category);
		$this->connector = Uuid\Uuid::fromString($connector);
		$this->parents = array_map(
			static fn (string $item): Uuid\UuidInterface => Uuid\Uuid::fromString($item),
			(array) $parents,
		);
		$this->children = array_map(
			static fn (string $item): Uuid\UuidInterface => Uuid\Uuid::fromString($item),
			(array) $children,
		);
		$this->properties = array_map(
			static fn (string $id): Uuid\UuidInterface => Uuid\Uuid::fromString($id),
			(array) $properties,
		);
		$this->controls = array_map(
			static fn (string $id): Uuid\UuidInterface => Uuid\Uuid::fromString($id),
			(array) $controls,
		);
		$this->channels = array_map(
			static fn (string $id): Uuid\UuidInterface => Uuid\Uuid::fromString($id),
			(array) $channels,
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

	public function getCategory(): Types\DeviceCategory
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

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getParents(): array
	{
		return $this->parents;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getChildren(): array
	{
		return $this->children;
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
	public function getChannels(): array
	{
		return $this->channels;
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
			'connector' => $this->getConnector()->toString(),
			'parents' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getParents(),
			),
			'children' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getChildren(),
			),
			'properties' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getProperties(),
			),
			'controls' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getControls(),
			),
			'channels' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getChannels(),
			),
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
