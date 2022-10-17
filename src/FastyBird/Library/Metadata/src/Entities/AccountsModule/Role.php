<?php declare(strict_types = 1);

/**
 * Role.php
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

namespace FastyBird\Metadata\Entities\AccountsModule;

use FastyBird\Metadata\Entities;
use Ramsey\Uuid;

/**
 * Role entity
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Role implements Entities\Entity
{

	private Uuid\UuidInterface $id;

	private Uuid\UuidInterface|null $parent;

	public function __construct(
		string $id,
		private readonly string $name,
		private readonly string $comment,
		private readonly bool $anonymous,
		private readonly bool $authenticated,
		private readonly bool $administrator,
		string|null $parent = null,
	)
	{
		$this->id = Uuid\Uuid::fromString($id);
		$this->parent = $parent !== null ? Uuid\Uuid::fromString($parent) : null;
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getComment(): string
	{
		return $this->comment;
	}

	public function isAnonymous(): bool
	{
		return $this->anonymous;
	}

	public function isAuthenticated(): bool
	{
		return $this->authenticated;
	}

	public function isAdministrator(): bool
	{
		return $this->administrator;
	}

	public function getParent(): Uuid\UuidInterface|null
	{
		return $this->parent;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'name' => $this->getName(),
			'comment' => $this->getComment(),
			'anonymous' => $this->isAnonymous(),
			'authenticated' => $this->isAuthenticated(),
			'administrator' => $this->isAdministrator(),
			'parent' => $this->getParent(),
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
