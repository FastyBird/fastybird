<?php declare(strict_types = 1);

namespace FastyBird\Library\Metadata\Tests\Cases\Unit\DI;

use FastyBird\Library\Metadata\Documents;
use FastyBird\Library\Metadata\Loaders;
use FastyBird\Library\Metadata\Schemas;
use FastyBird\Library\Metadata\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class MetadataExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testCompilersServices(): void
	{
		self::assertNotNull($this->container->getByType(Documents\DocumentFactory::class, false));

		self::assertNotNull($this->container->getByType(Loaders\MetadataLoader::class, false));

		self::assertNotNull($this->container->getByType(Schemas\Validator::class, false));
	}

}
