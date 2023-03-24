<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisExchangeWsExchange\Tests\Cases\Unit\DI;

use FastyBird\Bridge\RedisExchangeWsExchange\Subscribers;
use FastyBird\Bridge\RedisExchangeWsExchange\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class RedisExchangeWsExchangeExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->container->getByType(Subscribers\WsServer::class, false));
	}

}
