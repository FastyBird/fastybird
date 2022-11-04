<?php declare(strict_types = 1);

namespace FastyBird\Bridge\WsExchangeDevicesModule\Tests\Cases\Unit\DI;

use FastyBird\Bridge\WsExchangeDevicesModule\Subscribers;
use FastyBird\Bridge\WsExchangeDevicesModule\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class WsExchangeDevicesModuleExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->container->getByType(Subscribers\WsClient::class, false));
	}

}
