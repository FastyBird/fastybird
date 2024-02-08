<?php declare(strict_types = 1);

/**
 * IdentityFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Security
 * @since          1.0.0
 *
 * @date           15.07.20
 */

namespace FastyBird\Module\Accounts\Security;

use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\SimpleAuth\Models as SimpleAuthModels;
use FastyBird\SimpleAuth\Queries as SimpleAuthQueries;
use FastyBird\SimpleAuth\Security as SimpleAuthSecurity;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Lcobucci\JWT;

/**
 * Application identity factory
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Security
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class IdentityFactory implements SimpleAuthSecurity\IIdentityFactory
{

	public function __construct(
		private readonly SimpleAuthModels\Tokens\TokenRepository $tokenRepository,
	)
	{
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidState
	 */
	public function create(JWT\Token $token): SimpleAuthSecurity\IIdentity|null
	{
		/** @var SimpleAuthQueries\FindTokens<Entities\Tokens\AccessToken> $findToken */
		$findToken = new SimpleAuthQueries\FindTokens();
		$findToken->byToken($token->toString());

		$accessToken = $this->tokenRepository->findOneBy($findToken, Entities\Tokens\AccessToken::class);

		if ($accessToken instanceof Entities\Tokens\AccessToken) {
			return $accessToken->getIdentity();
		}

		return null;
	}

}
