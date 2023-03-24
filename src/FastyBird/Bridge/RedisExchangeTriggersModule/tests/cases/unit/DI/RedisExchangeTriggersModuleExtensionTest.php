<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisExchangeTriggersModule\Tests\Cases\Unit\DI;

use FastyBird\Bridge\RedisExchangeTriggersModule\Subscribers;
use FastyBird\Bridge\RedisExchangeTriggersModule\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class RedisExchangeTriggersModuleExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->container->getByType(Subscribers\RedisClient::class, false));
	}

}
