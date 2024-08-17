<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDbCache\Tests\Cases\Unit\DI;

use FastyBird\Plugin\RedisDbCache\Caching;
use FastyBird\Plugin\RedisDbCache\Clients;
use FastyBird\Plugin\RedisDbCache\Connections;
use FastyBird\Plugin\RedisDbCache\Tests;
use Nette;

final class RedisDbCacheExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->container->getByType(Connections\Configuration::class, false));

		self::assertNotNull($this->container->getByType(Clients\Client::class, false));

		self::assertNotNull($this->container->getByType(Caching\Storage::class, false));
		self::assertNull($this->container->getByType(Caching\Journal::class, false));
	}

}
