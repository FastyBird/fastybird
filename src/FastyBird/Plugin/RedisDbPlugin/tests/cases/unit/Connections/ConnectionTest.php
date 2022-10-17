<?php declare(strict_types = 1);

namespace FastyBird\RedisDbPlugin\Tests\Cases\Unit\Connections;

use FastyBird\RedisDbPlugin\Connections;
use PHPUnit\Framework\TestCase;

final class ConnectionTest extends TestCase
{

	public function testDefaultValues(): void
	{
		$config = new Connections\Connection('127.0.0.1', 1_234, null, null);

		self::assertSame('127.0.0.1', $config->getHost());
		self::assertSame(1_234, $config->getPort());
		self::assertNull($config->getUsername());
		self::assertNull($config->getPassword());
	}

}
