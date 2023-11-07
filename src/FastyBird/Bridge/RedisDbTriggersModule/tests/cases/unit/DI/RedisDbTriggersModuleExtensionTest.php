<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbTriggersModule\Tests\Cases\Unit\DI;

use FastyBird\Bridge\RedisDbTriggersModule\Models;
use FastyBird\Bridge\RedisDbTriggersModule\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class RedisDbTriggersModuleExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->container->getByType(Models\States\ActionsRepository::class, false));
		self::assertNotNull($this->container->getByType(Models\States\ActionsManager::class, false));
		self::assertNotNull($this->container->getByType(Models\States\ConditionsRepository::class, false));
		self::assertNotNull($this->container->getByType(Models\States\ConditionsManager::class, false));
	}

}
