<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbWsExchange\Tests\Cases\Unit\DI;

use FastyBird\Bridge\RedisDbWsExchange\Subscribers;
use FastyBird\Bridge\RedisDbWsExchange\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class RedisDbWsExchangeExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->container->getByType(Subscribers\WsServer::class, false));
	}

}
