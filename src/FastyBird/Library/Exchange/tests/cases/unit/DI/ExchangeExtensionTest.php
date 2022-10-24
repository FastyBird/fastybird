<?php declare(strict_types = 1);

namespace FastyBird\Library\Exchange\Tests\Cases\Unit\DI;

use FastyBird\Library\Exchange\Publisher;
use FastyBird\Library\Exchange\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class ExchangeExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testCompilersServices(): void
	{
		self::assertNotNull($this->container->getByType(Publisher\Container::class, false));
	}

}
