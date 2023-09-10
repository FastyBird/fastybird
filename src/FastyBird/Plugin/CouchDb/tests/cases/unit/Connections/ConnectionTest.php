<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\Tests\Cases\Unit\Connections;

use FastyBird\Plugin\CouchDb\Connections;
use PHPUnit\Framework\TestCase;

final class ConnectionTest extends TestCase
{

	public function testDefaultValues(): void
	{
		$config = new Connections\Connection('db.name', '127.0.0.1', 5_984);

		self::assertSame('127.0.0.1', $config->getHost());
		self::assertSame(5_984, $config->getPort());
		self::assertNull($config->getUsername());
		self::assertNull($config->getPassword());
		self::assertSame('db.name', $config->getDatabase());
	}

}
