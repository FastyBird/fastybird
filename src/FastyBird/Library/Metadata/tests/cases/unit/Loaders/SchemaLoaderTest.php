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
	public function XtestValidateValidInput(): void
	{
		$loader = new Loaders\SchemaLoader();

		$result = $loader->loadByRoutingKey(
			Metadata\Types\RoutingKey::get(Metadata\Types\RoutingKey::ROUTE_DEVICE_ENTITY_CREATED),
		);

		self::assertNotSame('', $result);
	}

}
