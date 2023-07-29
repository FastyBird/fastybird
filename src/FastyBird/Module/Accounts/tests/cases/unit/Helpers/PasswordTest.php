<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Helpers;

use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Helpers;
use PHPUnit\Framework\TestCase;

final class PasswordTest extends TestCase
{

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function XtestPassword(): void
	{
		$password = new Helpers\Password(null, 'somePassword');
		$hashedPassword = new Helpers\Password($password->getHash(), null, $password->getSalt());

		self::assertTrue($hashedPassword->isEqual('somePassword'));
	}

}
