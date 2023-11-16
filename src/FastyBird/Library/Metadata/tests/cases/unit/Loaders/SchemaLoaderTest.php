<?php declare(strict_types = 1);

namespace FastyBird\Library\Metadata\Tests\Cases\Unit\Loaders;

use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Loaders;
use FastyBird\Library\Metadata\Tests\Cases\Unit\BaseTestCase;

final class SchemaLoaderTest extends BaseTestCase
{

	/**
	 * @throws Metadata\Exceptions\FileNotFound
	 * @throws Metadata\Exceptions\InvalidArgument
	 */
	public function testValidateValidInput(): void
	{
		$loader = new Loaders\SchemaLoader();

		$result = $loader->loadByRoutingKey(
			Metadata\Types\RoutingKey::get(Metadata\Types\RoutingKey::DEVICE_DOCUMENT_CREATED),
		);

		self::assertNotSame('', $result);
	}

}
