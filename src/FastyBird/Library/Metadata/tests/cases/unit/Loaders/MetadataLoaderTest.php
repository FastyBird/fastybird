<?php declare(strict_types = 1);

namespace FastyBird\Library\Metadata\Tests\Cases\Unit\Loaders;

use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Loaders;
use FastyBird\Library\Metadata\Schemas;
use FastyBird\Library\Metadata\Tests\Cases\Unit\BaseTestCase;

final class MetadataLoaderTest extends BaseTestCase
{

	/**
	 * @throws Exceptions\FileNotFound
	 * @throws Exceptions\InvalidData
	 * @throws Exceptions\Logic
	 * @throws Exceptions\MalformedInput
	 */
	public function testLoadMetadata(): void
	{
		$loader = new Loaders\MetadataLoader(new Schemas\Validator());

		$loader->load();

		$this->expectNotToPerformAssertions();
	}

}
