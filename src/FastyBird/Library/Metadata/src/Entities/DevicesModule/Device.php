<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           04.06.22
 */

namespace FastyBird\Library\Metadata\Entities\DevicesModule;

use FastyBird\Library\Metadata\Entities;
use Nette\Utils;
use Ramsey\Uuid;
use function array_map;

/**
 * Device entity
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device implements Entities\Entity, Entities\Owner
{

	use Entities\TOwner;

	private Uuid\UuidInterface $id;

	private Uuid\UuidInterface $connector;

	/** @var Array<Uuid\UuidInterface> */
	private array $parents;

	/** @var Array<Uuid\UuidInterface> */
	private array $children;

	/**
	 * @param Array<string>|Utils\ArrayHash<string> $parents
	 * @param Array<string>|Utils\ArrayHash<string> $children
	 */
	public function __construct(
		string $id,
		private readonly string $type,
		private readonly string $identifier,
		string $connector,
		array|Utils\ArrayHash $parents,
		array|Utils\ArrayHash $children,
		private readonly string|null $name = null,
		private readonly string|null $comment = null,
		string|null $owner = null,
	)
	{
		$this->id = Uuid\Uuid::fromString($id);
		$this->connector = Uuid\Uuid::fromString($connector);
		$this->parents = array_map(
			static fn (string $item): Uuid\UuidInterface => Uuid\Uuid::fromString($item),
			(array) $parents,
		);
		$this->children = array_map(
			static fn (string $item): Uuid\UuidInterface => Uuid\Uuid::fromString($item),
			(array) $children,
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
	 * @return Array<Uuid\UuidInterface>
	 */
	public function getParents(): array
	{
		return $this->parents;
	}

	/**
	 * @return Array<Uuid\UuidInterface>
	 */
	public function getChildren(): array
	{
		return $this->children;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'type' => $this->getType(),
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'comment' => $this->getComment(),
			'connector' => $this->getConnector()->toString(),
			'parents' => array_map(
				static fn (Uuid\UuidInterface $parent): string => $parent->toString(),
				$this->getParents(),
			),
			'children' => array_map(
				static fn (Uuid\UuidInterface $child): string => $child->toString(),
				$this->getChildren(),
			),
			'owner' => $this->getOwner()?->toString(),
		];
	}

	/**
	 * @return Array<string, mixed>
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
