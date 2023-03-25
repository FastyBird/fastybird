<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisExchangeDevicesModule\Tests\Cases\Unit\DI;

use FastyBird\Bridge\RedisExchangeDevicesModule\Subscribers;
use FastyBird\Bridge\RedisExchangeDevicesModule\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class RedisExchangeDevicesModuleExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->container->getByType(Subscribers\RedisClient::class, false));
	}

}
