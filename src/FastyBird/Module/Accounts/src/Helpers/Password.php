<?php declare(strict_types = 1);

/**
 * Password.php
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

namespace FastyBird\Module\Accounts\Helpers;

use FastyBird\Module\Accounts\Exceptions;
use Nette;
use Nette\Utils;
use function hash;

/**
 * Password generator and verification
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Password
{

	use Nette\SmartObject;

	private const SEPARATOR = '##';

	private string $hash;

	private string $salt;

	private string $password;

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function __construct(
		string|null $hash = null,
		string|null $password = null,
		string|null $salt = null,
	)
	{
		if ($password !== null && $hash !== null) {
			throw new Exceptions\InvalidState('Only password string or hash could be provided');
		}

		if ($salt !== null) {
			$this->salt = $salt;

		} else {
			$this->createSalt();
		}

		if ($password !== null) {
			$this->setPassword($password, $salt);

		} elseif ($hash !== null) {
			$this->hash = $hash;

		} else {
			throw new Exceptions\InvalidState('Password or hash have to be provided');
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public static function createRandom(int $length = 4): self
	{
		if ($length < 1) {
			$length = 4;
		}

		return new self(null, Utils\Random::generate($length));
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public static function createFromString(string $password): self
	{
		return new self(null, $password);
	}

	public function createSalt(): string
	{
		return $this->salt = Utils\Random::generate(5);
	}

	public function getSalt(): string
	{
		return $this->salt;
	}

	public function setSalt(string $salt): void
	{
		$this->salt = $salt;
	}

	public function getHash(): string
	{
		return $this->hash;
	}

	public function getPassword(): string
	{
		return $this->password;
	}

	public function setPassword(string $password, string|null $salt = null): void
	{
		$this->password = $password;
		$this->salt = $salt ?? $this->createSalt();
		$this->hash = $this->hashPassword($password, $this->salt);
	}

	public function isEqual(string $password, string|null $salt = null): bool
	{
		if ($salt !== null) {
			$this->salt = $salt;
		}

		return $this->hash === $this->hashPassword($password, $this->salt);
	}

	private function hashPassword(string $password, string|null $salt = null): string
	{
		return hash('sha512', $salt . self::SEPARATOR . $password);
	}

	public function __toString(): string
	{
		return $this->hash;
	}

}
