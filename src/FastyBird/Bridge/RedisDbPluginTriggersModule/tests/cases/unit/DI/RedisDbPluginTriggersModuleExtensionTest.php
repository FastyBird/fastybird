<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginTriggersModule\Tests\Cases\Unit\DI;

use FastyBird\Bridge\RedisDbPluginTriggersModule\Models;
use FastyBird\Bridge\RedisDbPluginTriggersModule\Tests;
use Nette;

final class RedisDbPluginTriggersModuleExtensionTest extends Tests\Cases\Unit\BaseTestCase
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
