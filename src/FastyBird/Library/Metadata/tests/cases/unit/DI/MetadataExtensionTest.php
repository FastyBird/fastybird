<?php declare(strict_types = 1);

namespace FastyBird\Metadata\Tests\Cases\Unit\DI;

use FastyBird\Metadata\Loaders;
use FastyBird\Metadata\Schemas;
use FastyBird\Metadata\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class MetadataExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testCompilersServices(): void
	{
		self::assertNotNull($this->container->getByType(Loaders\SchemaLoader::class, false));
		self::assertNotNull($this->container->getByType(Loaders\MetadataLoader::class, false));
		self::assertNotNull($this->container->getByType(Schemas\Validator::class, false));
	}

}
