<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Connector\HomeKit\Hydrators;
use FastyBird\Connector\HomeKit\Schemas;
use FastyBird\Connector\HomeKit\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use Nette;

final class HomeKitExtensionTest extends BaseTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	public function XtestServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertNotNull($container->getByType(Schemas\HomeKitDevice::class, false));
		self::assertNotNull($container->getByType(Schemas\HomeKitConnector::class, false));

		self::assertNotNull($container->getByType(Hydrators\HomeKitDevice::class, false));
		self::assertNotNull($container->getByType(Hydrators\HomeKitConnector::class, false));
	}

}
