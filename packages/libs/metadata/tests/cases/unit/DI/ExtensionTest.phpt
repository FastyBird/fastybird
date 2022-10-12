<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\Metadata\Loaders;
use FastyBird\Metadata\Schemas;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../BaseTestCase.php';

/**
 * @testCase
 */
final class ExtensionTest extends BaseTestCase
{

	public function testCompilersServices(): void
	{
		Assert::notNull($this->container->getByType(Loaders\SchemaLoader::class));
		Assert::notNull($this->container->getByType(Loaders\MetadataLoader::class));
		Assert::notNull($this->container->getByType(Schemas\Validator::class));
	}

}

$test_case = new ExtensionTest();
$test_case->run();
