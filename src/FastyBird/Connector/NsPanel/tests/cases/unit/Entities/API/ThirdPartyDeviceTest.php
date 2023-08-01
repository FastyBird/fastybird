<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Tests\Cases\Unit\DI;

use FastyBird\Connector\NsPanel\Hydrators;
use FastyBird\Connector\NsPanel\Schemas;
use FastyBird\Connector\NsPanel\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use Nette;
use Orisai\ObjectMapper;

final class ThirdPartyDeviceTest extends BaseTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testCreateEntity(): void
	{
		$container = $this->createContainer();

		$container->getByType(ObjectMapper\Processing\Processor::class);

		self::assertFalse(true);
	}

}
