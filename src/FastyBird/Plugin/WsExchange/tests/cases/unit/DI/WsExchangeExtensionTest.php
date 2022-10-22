<?php declare(strict_types = 1);

namespace FastyBird\Plugin\WsExchange\Tests\Cases\Unit\DI;

use FastyBird\Plugin\WsExchange\Commands;
use FastyBird\Plugin\WsExchange\Controllers;
use FastyBird\Plugin\WsExchange\Publishers;
use FastyBird\Plugin\WsExchange\Server;
use FastyBird\Plugin\WsExchange\Subscribers;
use FastyBird\Plugin\WsExchange\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class WsExchangeExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->container->getByType(Controllers\Exchange::class, false));

		self::assertNotNull($this->container->getByType(Publishers\Publisher::class, false));

		self::assertNotNull($this->container->getByType(Commands\WsServer::class, false));

		self::assertNotNull($this->container->getByType(Server\Factory::class, false));

		self::assertNotNull($this->container->getByType(Subscribers\Client::class, false));
	}

}
