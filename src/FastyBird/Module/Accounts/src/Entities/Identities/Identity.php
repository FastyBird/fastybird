<?php declare(strict_types = 1);

/**
 * Identity.php
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

namespace FastyBird\Module\Accounts\Entities\Identities;

use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Helpers;
use FastyBird\SimpleAuth\Security as SimpleAuthSecurity;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Nette\Utils;
use Ramsey\Uuid;
use function array_map;
use function strval;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_accounts_module_identities",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Accounts identities"
 *     },
 *     uniqueConstraints={
 *       @ORM\UniqueConstraint(name="identity_uid_unique", columns={"identity_uid"})
 *     },
 *     indexes={
 *       @ORM\Index(name="identity_uid_idx", columns={"identity_uid"}),
 *       @ORM\Index(name="identity_state_idx", columns={"identity_state"})
 *     }
 * )
 */
class Identity implements Entities\Entity,
	SimpleAuthSecurity\IIdentity,
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
	 * @ORM\Column(type="uuid_binary", name="identity_id")
	 * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
	 */
	protected Uuid\UuidInterface $id;

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\ManyToOne(targetEntity="FastyBird\Module\Accounts\Entities\Accounts\Account", inversedBy="identities", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(name="account_id", referencedColumnName="account_id", onDelete="cascade", nullable=false)
	 */
	protected Entities\Accounts\Account $account;

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="string", name="identity_uid", length=50, nullable=false)
	 */
	protected string $uid;

	/**
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="text", name="identity_token", nullable=false)
	 */
	protected string $password;

	protected string|null $plainPassword = null;

	/**
	 * @var MetadataTypes\IdentityState
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
	 *
	 * @Enum(class=MetadataTypes\IdentityState::class)
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string_enum", name="identity_state", nullable=false, options={"default": "active"})
	 */
	protected $state;

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function __construct(
		Entities\Accounts\Account $account,
		string $uid,
		string $password,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->account = $account;
		$this->uid = $uid;

		$this->state = MetadataTypes\IdentityState::get(MetadataTypes\IdentityState::STATE_ACTIVE);

		$this->setPassword($password);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function verifyPassword(string $rawPassword): bool
	{
		return $this->getPassword()
			->isEqual($rawPassword, $this->getSalt());
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getPassword(): Helpers\Password
	{
		return $this->plainPassword !== null
			? new Helpers\Password(null, $this->plainPassword, $this->getSalt())
			: new Helpers\Password($this->password, null, $this->getSalt());
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setPassword(string|Helpers\Password $password): void
	{
		if ($password instanceof Helpers\Password) {
			$this->password = $password->getHash();

		} else {
			$password = Helpers\Password::createFromString($password);

			$this->password = $password->getHash();
			$this->plainPassword = $password->getPassword();
		}

		$this->setSalt($password->getSalt());
	}

	public function getSalt(): string|null
	{
		$salt = $this->getParam('salt');

		return $salt === null ? null : strval($salt);
	}

	public function setSalt(string $salt): void
	{
		$this->setParam('salt', $salt);
	}

	public function isActive(): bool
	{
		return $this->state === MetadataTypes\IdentityState::get(MetadataTypes\IdentityState::STATE_ACTIVE);
	}

	public function isBlocked(): bool
	{
		return $this->state === MetadataTypes\IdentityState::get(MetadataTypes\IdentityState::STATE_BLOCKED);
	}

	public function isDeleted(): bool
	{
		return $this->state === MetadataTypes\IdentityState::get(MetadataTypes\IdentityState::STATE_DELETED);
	}

	public function isInvalid(): bool
	{
		return $this->state === MetadataTypes\IdentityState::get(MetadataTypes\IdentityState::STATE_INVALID);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRoles(): array
	{
		return array_map(
			static fn (Entities\Roles\Role $role): string => $role->getName(),
			$this->account->getRoles(),
		);
	}

	public function invalidate(): void
	{
		$this->state = MetadataTypes\IdentityState::get(MetadataTypes\IdentityState::STATE_INVALID);
	}

	public function getAccount(): Entities\Accounts\Account
	{
		return $this->account;
	}

	public function getUid(): string
	{
		return $this->uid;
	}

	public function getState(): MetadataTypes\IdentityState
	{
		return $this->state;
	}

	public function setState(MetadataTypes\IdentityState $state): void
	{
		$this->state = $state;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getPlainId(),
			'account' => $this->getAccount()->getPlainId(),
			'uid' => $this->getUid(),
			'state' => $this->getState()->getValue(),
		];
	}

	/**
	 * @return void
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
	 */
	public function __clone()
	{
		$this->id = Uuid\Uuid::uuid4();
		$this->createdAt = new Utils\DateTime();
		$this->state = MetadataTypes\IdentityState::get(MetadataTypes\IdentityState::STATE_ACTIVE);
	}

	/**
	 * @throws Utils\JsonException
	 */
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
