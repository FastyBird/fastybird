<?php declare(strict_types = 1);

/**
 * Email.php
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

namespace FastyBird\Module\Accounts\Entities\Emails;

use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Types;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_accounts_module_emails",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Emails addresses"
 *     },
 *     uniqueConstraints={
 *       @ORM\UniqueConstraint(name="email_address_unique", columns={"email_address"})
 *     },
 *     indexes={
 *       @ORM\Index(name="email_address_idx", columns={"email_address"})
 *     }
 * )
 */
class Email implements Entities\Entity,
	DoctrineTimestampable\Entities\IEntityCreated,
	DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="uuid_binary", name="email_id")
	 * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
	 */
	protected Uuid\UuidInterface $id;

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\ManyToOne(targetEntity="FastyBird\Module\Accounts\Entities\Accounts\Account", inversedBy="emails")
	 * @ORM\JoinColumn(name="account_id", referencedColumnName="account_id", onDelete="cascade", nullable=false)
	 */
	private Entities\Accounts\Account $account;

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="string", name="email_address", unique=true, length=150, nullable=false)
	 */
	private string $address;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="boolean", name="email_default", length=1, nullable=false, options={"default": false})
	 */
	private bool $default = false;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="boolean", name="email_verified", length=1, nullable=false, options={"default": false})
	 */
	private bool $verified = false;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string", name="email_verification_hash", length=150, nullable=true, options={"default": null})
	 */
	private string|null $verificationHash = null;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="datetime", name="email_verification_created", nullable=true, options={"default": null})
	 */
	private DateTimeInterface|null $verificationCreated = null;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="datetime", name="email_verification_completed", nullable=true, options={"default": null})
	 */
	private DateTimeInterface|null $verificationCompleted = null;

	/**
	 * @var Types\EmailVisibility
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
	 *
	 * @Enum(class=Types\EmailVisibility::class)
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string_enum", name="email_visibility", nullable=false, options={"default": "public"})
	 */
	private $visibility;

	/**
	 * @throws Exceptions\EmailIsNotValid
	 */
	public function __construct(
		Entities\Accounts\Account $account,
		string $address,
		Uuid\UuidInterface|null $id = null,
	)
	{
		// @phpstan-ignore-next-line
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->account = $account;
		$this->visibility = Types\EmailVisibility::get(Types\EmailVisibility::VISIBILITY_PUBLIC);

		$this->setAddress($address);

		$account->addEmail($this);
	}

	public function getVerificationHash(): string|null
	{
		return $this->verificationHash;
	}

	public function setVerificationHash(string $verificationHash): void
	{
		$this->verificationHash = $verificationHash;
	}

	public function getVerificationCreated(): DateTimeInterface|null
	{
		return $this->verificationCreated;
	}

	public function setVerificationCreated(DateTimeInterface $verificationCreated): void
	{
		$this->verificationCreated = $verificationCreated;
	}

	public function getVerificationCompleted(): DateTimeInterface|null
	{
		return $this->verificationCompleted;
	}

	public function setVerificationCompleted(DateTimeInterface|null $verificationCompleted = null): void
	{
		$this->verificationCompleted = $verificationCompleted;
	}

	public function getAccount(): Entities\Accounts\Account
	{
		return $this->account;
	}

	public function getAddress(): string
	{
		return $this->address;
	}

	/**
	 * @throws Exceptions\EmailIsNotValid
	 */
	public function setAddress(string $address): void
	{
		if (!Utils\Validators::isEmail($address)) {
			throw new Exceptions\EmailIsNotValid('Invalid email address given');
		}

		$this->address = Utils\Strings::lower($address);
	}

	public function isDefault(): bool
	{
		return $this->default;
	}

	public function setDefault(bool $default): void
	{
		$this->default = $default;
	}

	public function isVerified(): bool
	{
		return $this->verified;
	}

	public function setVerified(bool $verified): void
	{
		$this->verified = $verified;
	}

	public function isPrivate(): bool
	{
		return $this->getVisibility()->equalsValue(Types\EmailVisibility::VISIBILITY_PRIVATE);
	}

	public function getVisibility(): Types\EmailVisibility
	{
		return $this->visibility;
	}

	public function setVisibility(Types\EmailVisibility $visibility): void
	{
		$this->visibility = $visibility;
	}

	public function isPublic(): bool
	{
		return $this->getVisibility()->equalsValue(Types\EmailVisibility::VISIBILITY_PUBLIC);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getPlainId(),
			'account' => $this->getAccount()->getPlainId(),
			'address' => $this->getAddress(),
			'default' => $this->isDefault(),
			'verified' => $this->isVerified(),
			'private' => $this->isPrivate(),
			'public' => $this->isPublic(),
		];
	}

	public function __toString(): string
	{
		return $this->address;
	}

}
