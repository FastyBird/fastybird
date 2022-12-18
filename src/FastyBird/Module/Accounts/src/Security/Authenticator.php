<?php declare(strict_types = 1);

/**
 * Authenticator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Security
 * @since          0.1.0
 *
 * @date           31.03.20
 */

namespace FastyBird\Module\Accounts\Security;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Models;
use FastyBird\SimpleAuth\Security as SimpleAuthSecurity;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use function is_string;

/**
 * Account authentication
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Security
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Authenticator implements SimpleAuthSecurity\IAuthenticator
{

	public const IDENTITY_UID_NOT_FOUND = 110;

	public const INVALID_CREDENTIAL_FOR_UID = 120;

	public const ACCOUNT_PROFILE_BLOCKED = 210;

	public const ACCOUNT_PROFILE_DELETED = 220;

	public const ACCOUNT_PROFILE_OTHER_ERROR = 230;

	public function __construct(
		private readonly Models\Identities\IdentitiesRepository $identitiesRepository,
	)
	{
	}

	/**
	 * Performs a system authentication
	 *
	 * @param array<mixed> $credentials
	 *
	 * @return Entities\Identities\Identity
	 *
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\AccountNotFound
	 * @throws Exceptions\AuthenticationFailed
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function authenticate(array $credentials): SimpleAuthSecurity\IIdentity
	{
		[$username, $password] = $credentials + [null, null];

		if (!is_string($username)) {
			throw new Exceptions\AccountNotFound('The identity identifier is incorrect', self::IDENTITY_UID_NOT_FOUND);
		}

		$identity = $this->identitiesRepository->findOneByUid($username);

		if ($identity === null) {
			throw new Exceptions\AccountNotFound('The identity identifier is incorrect', self::IDENTITY_UID_NOT_FOUND);
		}

		if (!is_string($password)) {
			throw new Exceptions\AuthenticationFailed('The password is incorrect', self::INVALID_CREDENTIAL_FOR_UID);
		}

		if (!$identity->verifyPassword($password)) {
			throw new Exceptions\AuthenticationFailed('The password is incorrect', self::INVALID_CREDENTIAL_FOR_UID);
		}

		$account = $identity->getAccount();

		if ($account->getState()
			->equalsValue(MetadataTypes\AccountState::STATE_ACTIVE)) {
			return $identity;
		}

		if ($account->getState()->equalsValue(MetadataTypes\AccountState::STATE_BLOCKED)) {
			throw new Exceptions\AuthenticationFailed(
				'Account profile is blocked',
				self::ACCOUNT_PROFILE_BLOCKED,
			);
		} elseif ($account->getState()->equalsValue(MetadataTypes\AccountState::STATE_DELETED)) {
			throw new Exceptions\AuthenticationFailed(
				'Account profile is deleted',
				self::ACCOUNT_PROFILE_DELETED,
			);
		}

		throw new Exceptions\AuthenticationFailed(
			'Account profile is not available',
			self::ACCOUNT_PROFILE_OTHER_ERROR,
		);
	}

}
