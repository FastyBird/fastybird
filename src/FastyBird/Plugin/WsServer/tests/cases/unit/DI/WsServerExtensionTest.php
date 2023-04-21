<?php declare(strict_types = 1);

namespace FastyBird\Plugin\WsServer\Tests\Cases\Unit\DI;

use FastyBird\Plugin\WsServer\Commands;
use FastyBird\Plugin\WsServer\Subscribers;
use FastyBird\Plugin\WsServer\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class WsServerExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->container->getByType(Commands\WsServer::class, false));

		self::assertNotNull($this->container->getByType(Subscribers\Client::class, false));
	}

}
