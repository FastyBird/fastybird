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
	public function XtestServicesRegistration(): void
	{
		self::assertNotNull($this->container->getByType(Models\ChannelPropertiesRepository::class, false));
		self::assertNotNull($this->container->getByType(Models\ChannelPropertiesManager::class, false));
		self::assertNotNull($this->container->getByType(Models\ConnectorPropertiesRepository::class, false));
		self::assertNotNull($this->container->getByType(Models\ConnectorPropertiesManager::class, false));
		self::assertNotNull($this->container->getByType(Models\DevicePropertiesRepository::class, false));
		self::assertNotNull($this->container->getByType(Models\DevicePropertiesManager::class, false));
	}

}
