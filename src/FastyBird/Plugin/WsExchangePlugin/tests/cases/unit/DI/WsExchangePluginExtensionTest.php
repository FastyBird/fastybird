<?php declare(strict_types = 1);

namespace FastyBird\WsExchangePlugin\Tests\Cases\Unit\DI;

use FastyBird\WsExchangePlugin\Controllers;
use FastyBird\WsExchangePlugin\Subscribers;
use FastyBird\WsExchangePlugin\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class WsExchangePluginExtensionTest extends BaseTestCase
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
