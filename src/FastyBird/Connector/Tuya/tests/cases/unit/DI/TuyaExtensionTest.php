<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Connector\Tuya\Hydrators;
use FastyBird\Connector\Tuya\Schemas;
use FastyBird\Connector\Tuya\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use Nette;

final class TuyaExtensionTest extends BaseTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertNotNull($container->getByType(Hydrators\TuyaConnector::class, false));
		self::assertNotNull($container->getByType(Hydrators\TuyaDevice::class, false));

		self::assertNotNull($container->getByType(Schemas\TuyaConnector::class, false));
		self::assertNotNull($container->getByType(Schemas\TuyaDevice::class, false));
	}

}
