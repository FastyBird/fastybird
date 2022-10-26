<?php declare(strict_types = 1);

/**
 * Details.php
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

namespace FastyBird\Module\Accounts\Entities\Details;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Module\Accounts\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Ramsey\Uuid;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_accounts_module_accounts_details",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Accounts details"
 *     }
 * )
 */
class Details implements Entities\Entity,
	DoctrineTimestampable\Entities\IEntityCreated,
	DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="uuid_binary", name="detail_id")
	 * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
	 */
	protected Uuid\UuidInterface $id;

	/**
	 * @phpcsSuppress SlevomatCodingStandard.Classes.UnusedPrivateElements.WriteOnlyProperty
	 *
	 * @ORM\OneToOne(targetEntity="FastyBird\Module\Accounts\Entities\Accounts\Account", inversedBy="details")
	 * @ORM\JoinColumn(name="account_id", referencedColumnName="account_id", unique=true, onDelete="cascade", nullable=false)
	 */
	private Entities\Accounts\Account $account;

	/**
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="string", name="detail_first_name", length=100, nullable=false)
	 */
	private string $firstName;

	/**
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="string", name="detail_last_name", length=100, nullable=false)
	 */
	private string $lastName;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string", name="detail_middle_name", length=100, nullable=true, options={"default": null})
	 */
	private string|null $middleName = null;

	public function __construct(
		Entities\Accounts\Account $account,
		string $firstName,
		string $lastName,
	)
	{
		$this->id = Uuid\Uuid::uuid4();

		$this->account = $account;

		$this->setFirstName($firstName);
		$this->setLastName($lastName);
	}

	public function getFirstName(): string
	{
		return $this->firstName;
	}

	public function setFirstName(string $firstName): void
	{
		$this->firstName = $firstName;
	}

	public function getLastName(): string
	{
		return $this->lastName;
	}

	public function setLastName(string $lastName): void
	{
		$this->lastName = $lastName;
	}

	public function getMiddleName(): string|null
	{
		return $this->middleName;
	}

	public function setMiddleName(string|null $middleName): void
	{
		$this->middleName = $middleName;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'first_name' => $this->getFirstName(),
			'last_name' => $this->getLastName(),
			'middle_name' => $this->getMiddleName(),
		];
	}

	public function __toString(): string
	{
		return $this->firstName . ' ' . $this->lastName;
	}

}
