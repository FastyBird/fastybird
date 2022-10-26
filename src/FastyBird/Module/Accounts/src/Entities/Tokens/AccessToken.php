<?php declare(strict_types = 1);

/**
 * AccessToken.php
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

namespace FastyBird\Module\Accounts\Entities\Tokens;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\SimpleAuth\Entities as SimpleAuthEntities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Ramsey\Uuid;

/**
 * @ORM\Entity
 */
class AccessToken extends SimpleAuthEntities\Tokens\Token implements
	Entities\Entity,
	Entities\EntityParams,
	DoctrineTimestampable\Entities\IEntityCreated,
	DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use Entities\TEntityParams;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	public const TOKEN_EXPIRATION = '+6 hours';

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\ManyToOne(targetEntity="FastyBird\Module\Accounts\Entities\Identities\Identity")
	 * @ORM\JoinColumn(name="identity_id", referencedColumnName="identity_id", onDelete="cascade", nullable=true)
	 */
	private Entities\Identities\Identity|null $identity = null;

	/**
	 * @IPubDoctrine\Crud(is={"writable"})
	 * @ORM\Column(name="token_valid_till", type="datetime", nullable=true)
	 */
	private DateTimeInterface|null $validTill = null;

	public function __construct(
		Entities\Identities\Identity $identity,
		string $token,
		DateTimeInterface|null $validTill,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($token, $id);

		$this->identity = $identity;
		$this->validTill = $validTill;
	}

	public function setRefreshToken(RefreshToken $refreshToken): void
	{
		parent::addChild($refreshToken);
	}

	public function getRefreshToken(): RefreshToken|null
	{
		$token = $this->children->first();

		if ($token instanceof RefreshToken) {
			return $token;
		}

		return null;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getIdentity(): Entities\Identities\Identity
	{
		if ($this->identity === null) {
			throw new Exceptions\InvalidState('Identity is not set to token.');
		}

		return $this->identity;
	}

	public function getValidTill(): DateTimeInterface|null
	{
		return $this->validTill;
	}

	public function isValid(DateTimeInterface $dateTime): bool
	{
		if ($this->validTill === null) {
			return true;
		}

		return $this->validTill >= $dateTime;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getPlainId(),
		];
	}

}
