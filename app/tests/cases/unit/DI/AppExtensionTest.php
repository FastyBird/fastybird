<?php declare(strict_types = 1);

namespace FastyBird\App\Tests\Cases\Unit\DI;

use Error;
use FastyBird\App\Tests;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;

final class AppExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Error
	 *
	 * @doesNotPerformAssertions
	 */
	public function testServicesRegistration(): void
	{
		$this->createContainer();
	}

}
