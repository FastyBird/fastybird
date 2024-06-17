<?php declare(strict_types = 1);

namespace FastyBird\App\Tests\Cases\Unit\DI;

use Error;
use FastyBird\App\Router;
use FastyBird\App\Tests;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use Nette;

final class AppExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertNotNull($container->getByType(Router\AppRouter::class, false));
	}

}
