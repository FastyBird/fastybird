<?php declare(strict_types = 1);

namespace FastyBird\Library\Application\Tests\Cases\Unit\DI;

use FastyBird\Library\Application\Boot;
use FastyBird\Library\Application\Exceptions;
use FastyBird\Library\Application\Helpers;
use Monolog;
use Nette;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Monolog as SymfonyMonolog;

final class ExtensionTest extends TestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testCompilersServices(): void
	{
		$configurator = Boot\Bootstrap::boot();

		$container = $configurator->createContainer();

		self::assertNotNull($container->getByType(Monolog\Handler\RotatingFileHandler::class, false));
		self::assertNull($container->getByType(SymfonyMonolog\Handler\ConsoleHandler::class, false));

		self::assertNotNull($container->getByType(Helpers\Database::class, false));
	}

}
