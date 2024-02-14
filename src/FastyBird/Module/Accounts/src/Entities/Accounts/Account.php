<?php declare(strict_types = 1);

/**
 * Account.php
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

namespace FastyBird\Module\Accounts\Entities\Accounts;

use DateTimeInterface;
use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Types;
use IPub\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Nette\Utils;
use Ramsey\Uuid;
use function array_map;
use function assert;
use function in_array;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_accounts_module_accounts',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Application accounts',
	],
)]
class Account implements Entities\Entity,
	Entities\EntityParams,
	DoctrineTimestampable\Entities\IEntityCreated,
	DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use Entities\TEntityParams;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'account_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(
		name: 'account_state',
		type: 'string',
		nullable: false,
		enumType: Types\AccountState::class,
		options: ['default' => Types\AccountState::NOT_ACTIVATED],
	)]
	protected Types\AccountState $state;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'account_request_hash', type: 'string', nullable: true, options: ['default' => null])]
	protected string|null $requestHash = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'account_last_visit', type: 'datetime', nullable: true, options: ['default' => null])]
	protected DateTimeInterface|null $lastVisit = null;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\OneToOne(
		mappedBy: 'account',
		targetEntity: Entities\Details\Details::class,
		cascade: ['persist', 'remove'],
	)]
	protected Entities\Details\Details|null $details;

	/** @var Common\Collections\Collection<int, Entities\Identities\Identity> */
	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\OneToMany(
		mappedBy: 'account',
		targetEntity: Entities\Identities\Identity::class,
	)]
	protected Common\Collections\Collection $identities;

	/** @var Common\Collections\Collection<int, Entities\Emails\Email> */
	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\OneToMany(
		mappedBy: 'account',
		targetEntity: Entities\Emails\Email::class,
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	protected Common\Collections\Collection $emails;

	/** @var Common\Collections\Collection<int, Entities\Roles\Role> */
	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\ManyToMany(targetEntity: Entities\Roles\Role::class)]
	#[ORM\JoinTable(
		name: 'fb_accounts_module_accounts_roles',
		joinColumns: [
			new ORM\JoinColumn(
				name: 'account_id',
				referencedColumnName: 'account_id',
				onDelete: 'CASCADE',
			),
		],
		inverseJoinColumns: [
			new ORM\JoinColumn(
				name: 'role_id',
				referencedColumnName: 'role_id',
				onDelete: 'CASCADE',
			),
		],
	)]
	protected Common\Collections\Collection $roles;

	public function __construct(Uuid\UuidInterface|null $id = null)
	{
		// @phpstan-ignore-next-line
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->state = Types\AccountState::NOT_ACTIVATED;

		$this->emails = new Common\Collections\ArrayCollection();
		$this->identities = new Common\Collections\ArrayCollection();
		$this->roles = new Common\Collections\ArrayCollection();
	}

	public function isActivated(): bool
	{
		return $this->state === Types\AccountState::ACTIVE;
	}

	public function isBlocked(): bool
	{
		return $this->state === Types\AccountState::BLOCKED;
	}

	public function isDeleted(): bool
	{
		return $this->state === Types\AccountState::DELETED;
	}

	public function isNotActivated(): bool
	{
		return $this->state === Types\AccountState::NOT_ACTIVATED;
	}

	public function isApprovalRequired(): bool
	{
		return $this->state === Types\AccountState::APPROVAL_WAITING;
	}

	public function getRequestHash(): string|null
	{
		return $this->requestHash;
	}

	public function setRequestHash(string $requestHash): void
	{
		$this->requestHash = $requestHash;
	}

	public function getName(): string
	{
		assert($this->details instanceof Entities\Details\Details);

		return $this->details->getLastName() . ' ' . $this->details->getFirstName();
	}

	public function getDetails(): Entities\Details\Details
	{
		assert($this->details instanceof Entities\Details\Details);

		return $this->details;
	}

	public function getState(): Types\AccountState
	{
		return $this->state;
	}

	public function setState(Types\AccountState $state): void
	{
		$this->state = $state;
	}

	public function getLastVisit(): DateTimeInterface|null
	{
		return $this->lastVisit;
	}

	public function setLastVisit(DateTimeInterface $lastVisit): void
	{
		$this->lastVisit = $lastVisit;
	}

	/**
	 * @return array<Entities\Identities\Identity>
	 */
	public function getIdentities(): array
	{
		return $this->identities->toArray();
	}

	/**
	 * @return array<Entities\Emails\Email>
	 */
	public function getEmails(): array
	{
		return $this->emails->toArray();
	}

	public function getEmail(string|null $id = null): Entities\Emails\Email|null
	{
		$email = $this->emails
			->filter(static fn (Entities\Emails\Email $row): bool => $id !== null && $id !== '' ? $row->getId()
				->equals(Uuid\Uuid::fromString($id)) : $row->isDefault())
			->first();

		return $email !== false ? $email : null;
	}

	/**
	 * @param array<Entities\Emails\Email> $emails
	 */
	public function setEmails(array $emails): void
	{
		$this->emails = new Common\Collections\ArrayCollection();

		foreach ($emails as $entity) {
			$this->addEmail($entity);
		}
	}

	public function addEmail(Entities\Emails\Email $email): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->emails->contains($email)) {
			// ...and assign it to collection
			$this->emails->add($email);
		}
	}

	public function removeEmail(Entities\Emails\Email $email): void
	{
		// Check if collection contain removing entity...
		if ($this->emails->contains($email)) {
			// ...and remove it from collection
			$this->emails->removeElement($email);
		}
	}

	/**
	 * @return array<Entities\Roles\Role>
	 */
	public function getRoles(): array
	{
		return $this->roles->toArray();
	}

	/**
	 * @param array<Entities\Roles\Role> $roles
	 */
	public function setRoles(array $roles): void
	{
		$this->roles = new Common\Collections\ArrayCollection();

		foreach ($roles as $entity) {
			$this->addRole($entity);
		}

		foreach ($this->roles as $entity) {
			if (!in_array($entity, $roles, true)) {
				$this->roles->removeElement($entity);
			}
		}
	}

	public function addRole(Entities\Roles\Role $role): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->roles->contains($role)) {
			// ...and assign it to collection
			$this->roles->add($role);
		}
	}

	public function removeRole(Entities\Roles\Role $role): void
	{
		// Check if collection contain removing entity...
		if ($this->roles->contains($role)) {
			// ...and remove it from collection
			$this->roles->removeElement($role);
		}
	}

	public function hasRole(string $role): bool
	{
		$role = $this->roles
			->filter(static fn (Entities\Roles\Role $row): bool => $role === $row->getName())
			->first();

		return $role !== false;
	}

	/**
	 * TODO: Should be refactored
	 */
	public function getLanguage(): string
	{
		return 'en';
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getPlainId(),
			'first_name' => $this->getDetails()->getFirstName(),
			'last_name' => $this->getDetails()->getLastName(),
			'middle_name' => $this->getDetails()->getMiddleName(),
			'email' => $this->getEmail()?->getAddress(),
			'state' => $this->getState()->value,
			'registered' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'last_visit' => $this->getLastVisit()?->format(DateTimeInterface::ATOM),
			'roles' => array_map(static fn (Entities\Roles\Role $role): string => $role->getName(), $this->getRoles()),
			'language' => $this->getLanguage(),
		];
	}

	/**
	 * @throws Utils\JsonException
	 */
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
