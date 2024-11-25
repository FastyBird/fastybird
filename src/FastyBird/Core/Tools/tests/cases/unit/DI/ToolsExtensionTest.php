<?php declare(strict_types = 1);

namespace FastyBird\Core\Tools\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Core\Tools\Helpers;
use FastyBird\Core\Tools\Schemas;
use FastyBird\Core\Tools\Tests;
use Nette;

final class ToolsExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testCompilersServices(): void
	{
		$container = $this->getContainer();

		self::assertNotNull($container->getByType(Helpers\Database::class, false));

		self::assertNotNull($container->getByType(Schemas\Validator::class, false));
	}

}
