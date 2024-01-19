<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Tests\Cases\Unit\DI;

use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Connections;
use FastyBird\Plugin\RedisDb\Exchange;
use FastyBird\Plugin\RedisDb\Models;
use FastyBird\Plugin\RedisDb\States;
use FastyBird\Plugin\RedisDb\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Plugin\RedisDb\Utilities;
use Nette;

final class RedisDbExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->container->getByType(Connections\Configuration::class, false));

		self::assertNotNull($this->container->getByType(Clients\Client::class, false));
		self::assertNotNull($this->container->getByType(Clients\Async\Client::class, false));

		self::assertNotNull($this->container->getByType(States\StateFactory::class, false));

		self::assertNotNull($this->container->getByType(Models\States\StatesManagerFactory::class, false));
		self::assertNotNull($this->container->getByType(Models\States\StatesRepositoryFactory::class, false));
		self::assertNotNull($this->container->getByType(Models\States\Async\StatesManagerFactory::class, false));
		self::assertNotNull($this->container->getByType(Models\States\Async\StatesRepositoryFactory::class, false));

		self::assertNotNull($this->container->getByType(Exchange\Factory::class, false));
		self::assertNotNull($this->container->getByType(Exchange\Handler::class, false));

		self::assertNotNull($this->container->getByType(Utilities\IdentifierGenerator::class, false));
	}

}
