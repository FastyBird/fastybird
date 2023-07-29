<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Tests\Cases\Unit\DI;

use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Connections;
use FastyBird\Plugin\RedisDb\Handlers;
use FastyBird\Plugin\RedisDb\Models;
use FastyBird\Plugin\RedisDb\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Plugin\RedisDb\Utilities;
use Nette;

final class RedisDbExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function XtestServicesRegistration(): void
	{
		self::assertNotNull($this->container->getByType(Connections\Connection::class, false));

		self::assertNotNull($this->container->getByType(Clients\Client::class, false));
		self::assertNotNull($this->container->getByType(Clients\Factory::class, false));

		self::assertNotNull($this->container->getByType(Models\StatesManagerFactory::class, false));
		self::assertNotNull($this->container->getByType(Models\StatesRepositoryFactory::class, false));

		self::assertNotNull($this->container->getByType(Handlers\Message::class, false));

		self::assertNotNull($this->container->getByType(Utilities\IdentifierGenerator::class, false));
	}

}
