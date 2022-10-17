<?php declare(strict_types = 1);

namespace FastyBird\Metadata\Tests\Cases\Unit\Loaders;

use FastyBird\Metadata\Exceptions;
use FastyBird\Metadata\Loaders;
use FastyBird\Metadata\Schemas;
use FastyBird\Metadata\Tests\Cases\Unit\BaseTestCase;

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
