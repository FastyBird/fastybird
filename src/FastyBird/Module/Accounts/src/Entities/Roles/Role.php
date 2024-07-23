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
use FastyBird\Library\Application\Entities\Mapping as ApplicationMapping;
use FastyBird\Module\Accounts\Entities;
use FastyBird\SimpleAuth\Entities as SimpleAuthEntities;
use FastyBird\SimpleAuth\Types as SimpleAuthTypes;
use IPub\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Ramsey\Uuid;
use function array_map;
use function assert;
use function in_array;
use function is_string;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'policy_name_unique', columns: ['parent_id', 'policy_v0'])]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class Role extends SimpleAuthEntities\Policies\Policy implements Entities\Entity,
	DoctrineTimestampable\Entities\IEntityCreated,
	DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	public const TYPE = 'user_role';

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'policy_comment', type: 'string', nullable: false)]
	private string $comment;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
	#[ORM\JoinColumn(
		name: 'parent_id',
		referencedColumnName: 'policy_id',
		nullable: true,
		onDelete: 'set null',
	)]
	private self|null $parent = null;

	/** @var Common\Collections\Collection<int, Role> */
	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
	private Common\Collections\Collection $children;

	public function __construct(
		string $v0,
		string $comment,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct(SimpleAuthTypes\PolicyType::POLICY, $id);

		$this->setV0($v0);
		$this->setComment($comment);

		$this->children = new Common\Collections\ArrayCollection();
	}

	public function getName(): string
	{
		assert(is_string($this->getV0()));

		return $this->getV0();
	}

	public function setName(string $name): void
	{
		$this->setV0($name);
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

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'source' => $this->getSource()->value,
			'name' => $this->getV0(),
			'comment' => $this->getComment(),
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
