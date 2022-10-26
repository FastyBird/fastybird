<?php declare(strict_types = 1);

/**
 * Account.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           30.03.20
 */

namespace FastyBird\Module\Accounts\Entities\Accounts;

use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use DateTimeInterface;
use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Accounts\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Ramsey\Uuid;
use function array_map;
use function assert;
use function in_array;
use const DATE_ATOM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_accounts_module_accounts",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Application accounts"
 *     }
 * )
 */
class Account implements Entities\Entity,
	Entities\EntityParams,
	DoctrineTimestampable\Entities\IEntityCreated,
	DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use Entities\TEntityParams;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="uuid_binary", name="account_id")
	 * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
	 */
	protected Uuid\UuidInterface $id;

	/**
	 * @var MetadataTypes\AccountState
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
	 *
	 * @Enum(class=MetadataTypes\AccountState::class)
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string_enum", name="account_state", nullable=false, options={"default": "notActivated"})
	 */
	protected $state;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string", name="account_request_hash", nullable=true, options={"default": null})
	 */
	protected string|null $requestHash = null;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="datetime", name="account_last_visit", nullable=true, options={"default": null})
	 */
	protected DateTimeInterface|null $lastVisit = null;

	/**
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\OneToOne(targetEntity="FastyBird\Module\Accounts\Entities\Details\Details", mappedBy="account", cascade={"persist", "remove"})
	 */
	protected Entities\Details\Details|null $details;

	/**
	 * @var Common\Collections\Collection<int, Entities\Identities\Identity>
	 *
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\OneToMany(targetEntity="FastyBird\Module\Accounts\Entities\Identities\Identity", mappedBy="account")
	 */
	protected Common\Collections\Collection $identities;

	/**
	 * @var Common\Collections\Collection<int, Entities\Emails\Email>
	 *
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\OneToMany(targetEntity="FastyBird\Module\Accounts\Entities\Emails\Email", mappedBy="account", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	protected Common\Collections\Collection $emails;

	/**
	 * @var Common\Collections\Collection<int, Entities\Roles\Role>
	 *
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\ManyToMany(targetEntity="FastyBird\Module\Accounts\Entities\Roles\Role")
	 * @ORM\JoinTable(name="fb_accounts_module_accounts_roles",
	 *    joinColumns={
	 *       @ORM\JoinColumn(name="account_id", referencedColumnName="account_id", onDelete="cascade")
	 *    },
	 *    inverseJoinColumns={
	 *       @ORM\JoinColumn(name="role_id", referencedColumnName="role_id", onDelete="cascade")
	 *    }
	 * )
	 */
	protected Common\Collections\Collection $roles;

	public function __construct(Uuid\UuidInterface|null $id = null)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->state = MetadataTypes\AccountState::get(MetadataTypes\AccountState::STATE_NOT_ACTIVATED);

		$this->emails = new Common\Collections\ArrayCollection();
		$this->identities = new Common\Collections\ArrayCollection();
		$this->roles = new Common\Collections\ArrayCollection();
	}

	public function isActivated(): bool
	{
		return $this->state->equalsValue(MetadataTypes\AccountState::STATE_ACTIVE);
	}

	public function isBlocked(): bool
	{
		return $this->state->equalsValue(MetadataTypes\AccountState::STATE_BLOCKED);
	}

	public function isDeleted(): bool
	{
		return $this->state->equalsValue(MetadataTypes\AccountState::STATE_DELETED);
	}

	public function isNotActivated(): bool
	{
		return $this->state->equalsValue(MetadataTypes\AccountState::STATE_NOT_ACTIVATED);
	}

	public function isApprovalRequired(): bool
	{
		return $this->state->equalsValue(MetadataTypes\AccountState::STATE_APPROVAL_WAITING);
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

	public function getState(): MetadataTypes\AccountState
	{
		return $this->state;
	}

	public function setState(MetadataTypes\AccountState $state): void
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
	 * @phpstan-param array<Entities\Emails\Email> $emails
	 */
	public function setEmails(array $emails): void
	{
		$this->emails = new Common\Collections\ArrayCollection();

		foreach ($emails as $entity) {
			$this->emails->add($entity);
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
	 * @phpstan-param array<Entities\Roles\Role> $roles
	 */
	public function setRoles(array $roles): void
	{
		$this->roles = new Common\Collections\ArrayCollection();

		foreach ($roles as $entity) {
			$this->roles->add($entity);
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
			'state' => $this->getState()->getValue(),
			'registered' => $this->getCreatedAt()?->format(DATE_ATOM),
			'last_visit' => $this->getLastVisit()?->format(DATE_ATOM),
			'roles' => array_map(static fn (Entities\Roles\Role $role): string => $role->getName(), $this->getRoles()),
			'language' => $this->getLanguage(),
		];
	}

}
