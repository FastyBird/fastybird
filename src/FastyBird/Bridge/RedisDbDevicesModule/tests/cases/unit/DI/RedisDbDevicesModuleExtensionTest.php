<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbDevicesModule\Tests\Cases\Unit\DI;

use FastyBird\Bridge\RedisDbDevicesModule\Models;
use FastyBird\Bridge\RedisDbDevicesModule\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class RedisDbDevicesModuleExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->container->getByType(Models\States\ChannelPropertiesRepository::class, false));
		self::assertNotNull($this->container->getByType(Models\States\ChannelPropertiesManager::class, false));
		self::assertNotNull($this->container->getByType(Models\States\ConnectorPropertiesRepository::class, false));
		self::assertNotNull($this->container->getByType(Models\States\ConnectorPropertiesManager::class, false));
		self::assertNotNull($this->container->getByType(Models\States\DevicePropertiesRepository::class, false));
		self::assertNotNull($this->container->getByType(Models\States\DevicePropertiesManager::class, false));

		self::assertNotNull($this->container->getByType(
			Models\States\Async\ChannelPropertiesRepository::class,
			false,
		));
		self::assertNotNull($this->container->getByType(
			Models\States\Async\ChannelPropertiesManager::class,
			false,
		));
		self::assertNotNull($this->container->getByType(
			Models\States\Async\ConnectorPropertiesRepository::class,
			false,
		));
		self::assertNotNull($this->container->getByType(
			Models\States\Async\ConnectorPropertiesManager::class,
			false,
		));
		self::assertNotNull($this->container->getByType(
			Models\States\Async\DevicePropertiesRepository::class,
			false,
		));
		self::assertNotNull($this->container->getByType(
			Models\States\Async\DevicePropertiesManager::class,
			false,
		));
	}

}
