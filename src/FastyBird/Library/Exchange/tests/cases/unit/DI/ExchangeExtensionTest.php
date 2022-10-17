<?php declare(strict_types = 1);

namespace FastyBird\Exchange\Tests\Cases\Unit\DI;

use FastyBird\Exchange\Consumer;
use FastyBird\Exchange\Entities;
use FastyBird\Exchange\Publisher;
use FastyBird\Exchange\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class ExchangeExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testCompilersServices(): void
	{
		self::assertNotNull($this->container->getByType(Entities\EntityFactory::class, false));
		self::assertNotNull($this->container->getByType(Publisher\Container::class, false));
		self::assertNotNull($this->container->getByType(Consumer\Container::class, false));
	}

}
