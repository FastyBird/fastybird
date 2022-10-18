<?php declare(strict_types = 1);

namespace FastyBird\Plugin\WsExchange\Tests\Cases\Unit\DI;

use FastyBird\Plugin\WsExchange\Controllers;
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
		self::assertNotNull($this->container->getByType(Subscribers\Client::class, false));

		self::assertNotNull($this->container->getByType(Controllers\Exchange::class, false));
	}

}
