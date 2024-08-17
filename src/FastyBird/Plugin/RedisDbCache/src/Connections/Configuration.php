<?php declare(strict_types = 1);

/**
 * Configuration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbCachePlugin!
 * @subpackage     Connections
 * @since          1.0.0
 *
 * @date           15.08.24
 */

namespace FastyBird\Plugin\RedisDbCache\Connections;

use Nette;

/**
 * Redis connection configuration
 *
 * @package        FastyBird:RedisDbCachePlugin!
 * @subpackage     Connections
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Configuration
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $host = '127.0.0.1',
		private readonly int $port = 6_379,
		private readonly string|null $username = null,
		private readonly string|null $password = null,
		private readonly int $database = 0,
	)
	{
	}

	public function getHost(): string
	{
		return $this->host;
	}

	public function getPort(): int
	{
		return $this->port;
	}

	public function getUsername(): string|null
	{
		return $this->username;
	}

	public function getPassword(): string|null
	{
		return $this->password;
	}

	public function getDatabase(): int
	{
		return $this->database;
	}

}
