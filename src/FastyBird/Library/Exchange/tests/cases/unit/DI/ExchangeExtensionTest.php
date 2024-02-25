<?php declare(strict_types = 1);

namespace FastyBird\Library\Exchange\Tests\Cases\Unit\DI;

use FastyBird\Library\Exchange\Consumers;
use FastyBird\Library\Exchange\Documents;
use FastyBird\Library\Exchange\Publisher;
use FastyBird\Library\Exchange\Tests;
use Nette;

final class ExchangeExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testCompilersServices(): void
	{
		self::assertNotNull($this->container->getByType(Documents\DocumentFactory::class, false));

		self::assertNotNull($this->container->getByType(Publisher\Container::class, false));
		self::assertNotNull($this->container->getByType(Publisher\Async\Container::class, false));

		self::assertNotNull($this->container->getByType(Consumers\Container::class, false));
	}

}
