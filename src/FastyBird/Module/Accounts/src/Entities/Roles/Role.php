<?php declare(strict_types = 1);

/**
 * Role.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           30.03.20
 */

namespace FastyBird\Module\Accounts\Entities\Roles;

use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Module\Accounts\Entities;
use FastyBird\SimpleAuth\Constants as SimpleAuthConstants;
use IPub\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Ramsey\Uuid;
use function array_map;
use function in_array;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_accounts_module_acl_roles',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'ACL roles',
	],
)]
#[ORM\UniqueConstraint(name: 'role_name_unique', columns: ['parent_id', 'role_name'])]
class Role implements Entities\Entity,
	DoctrineTimestampable\Entities\IEntityCreated,
	DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'role_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[ORM\Column(name: 'role_name', type: 'string', length: 100, nullable: false)]
	private string $name;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'role_comment', type: 'string', nullable: false)]
	private string $comment;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
	#[ORM\JoinColumn(
		name: 'parent_id',
		referencedColumnName: 'role_id',
		nullable: true,
		onDelete: 'set null',
	)]
	private self|null $parent = null;

	/** @var Common\Collections\Collection<int, Role> */
	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
	private Common\Collections\Collection $children;

	public function __construct(
		string $name,
		string $comment,
		Uuid\UuidInterface|null $id = null,
	)
	{
		// @phpstan-ignore-next-line
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->name = $name;
		$this->comment = $comment;

		$this->children = new Common\Collections\ArrayCollection();
	}

	public function getComment(): string
	{
		return $this->comment;
	}

	public function setComment(string $comment): void
	{
		$this->comment = $comment;
	}

	public function getParent(): self|null
	{
		return $this->parent;
	}

	public function setParent(self|null $parent = null): void
	{
		if ($parent !== null) {
			$parent->addChild($this);
		}

		$this->parent = $parent;
	}

	public function addChild(self $child): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->children->contains($child)) {
			// ...and assign it to collection
			$this->children->add($child);
		}
	}

	/**
	 * @return array<Role>
	 */
	public function getChildren(): array
	{
		return $this->children->toArray();
	}

	/**
	 * @param array<Role> $children
	 */
	public function setChildren(array $children): void
	{
		$this->children = new Common\Collections\ArrayCollection();

		foreach ($children as $entity) {
			$this->addChild($entity);
		}

		foreach ($this->children as $entity) {
			if (!in_array($entity, $children, true)) {
				$this->children->removeElement($entity);
			}
		}
	}

	public function isAnonymous(): bool
	{
		return $this->name === SimpleAuthConstants::ROLE_ANONYMOUS;
	}

	public function isAuthenticated(): bool
	{
		return in_array($this->name, [
			SimpleAuthConstants::ROLE_MANAGER,
			SimpleAuthConstants::ROLE_USER,
			SimpleAuthConstants::ROLE_VISITOR,
		], true);
	}

	public function isAdministrator(): bool
	{
		return $this->name === SimpleAuthConstants::ROLE_ADMINISTRATOR;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'source' => $this->getSource()->value,
			'name' => $this->getName(),
			'comment' => $this->getComment(),
			'is_administrator' => $this->isAdministrator(),
			'is_authenticated' => $this->isAuthenticated(),
			'is_anonymous' => $this->isAnonymous(),
			'parent' => $this->getParent()?->getId()->toString(),
			'children' => array_map(static fn (self $role): string => $role->getId()->toString(), $this->getChildren()),
		];
	}

	/**
	 * Convert role object to string
	 */
	public function __toString(): string
	{
		return $this->getName();
	}

}
