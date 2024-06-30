<?php declare(strict_types = 1);

/**
 * Application.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           20.01.24
 */

namespace FastyBird\Module\Accounts\Subscribers;

use FastyBird\Module\Accounts\Events;
use FastyBird\SimpleAuth\Exceptions as SimpleAuthExceptions;
use FastyBird\SimpleAuth\Security as SimpleAuthSecurity;
use Lcobucci\JWT;
use Nette;
use Nette\Http;
use Symfony\Component\EventDispatcher;
use Throwable;
use function is_string;

/**
 * Application UI events
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Application implements EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public function __construct(
		private readonly SimpleAuthSecurity\IIdentityFactory $identityFactory,
		private readonly SimpleAuthSecurity\User $user,
		private readonly SimpleAuthSecurity\TokenValidator $tokenValidator,
		private readonly Http\RequestFactory $requestFactory,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\Request::class => 'request',
		];
	}

	public function request(): void
	{
		try {
			$token = $this->getToken();

			if ($token !== null) {
				$identity = $this->identityFactory->create($token);

				if ($identity !== null) {
					$this->user->login($identity);

					return;
				}
			}
		} catch (Throwable) {
			// Just ignore it
		}

		$this->user->logout();
	}

	/**
	 * @throws SimpleAuthExceptions\UnauthorizedAccess
	 */
	private function getToken(): JWT\UnencryptedToken|null
	{
		$request = $this->requestFactory->fromGlobals();

		$token = $request->getCookie('token');

		if (is_string($token)) {
			$token = $this->tokenValidator->validate($token);

			if ($token === null) {
				return null;
			}

			return $token;
		}

		return null;
	}

}
