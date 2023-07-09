<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Tests\Cases\Unit\DI;

use FastyBird\Connector\NsPanel\Hydrators;
use FastyBird\Connector\NsPanel\Schemas;
use FastyBird\Connector\NsPanel\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use Nette;

final class NsPanelExtensionTest extends BaseTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertNotNull($container->getByType(Hydrators\NsPanelConnector::class, false));
		self::assertNotNull($container->getByType(Hydrators\NsPanelDevice::class, false));

		self::assertNotNull($container->getByType(Schemas\NsPanelConnector::class, false));
		self::assertNotNull($container->getByType(Schemas\NsPanelDevice::class, false));
	}

}
