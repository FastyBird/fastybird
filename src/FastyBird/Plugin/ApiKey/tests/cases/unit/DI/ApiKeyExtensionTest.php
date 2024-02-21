<?php declare(strict_types = 1);

namespace FastyBird\Plugin\ApiKey\Tests\Cases\Unit\DI;

use FastyBird\Plugin\ApiKey\Commands;
use FastyBird\Plugin\ApiKey\Middleware;
use FastyBird\Plugin\ApiKey\Models;
use FastyBird\Plugin\ApiKey\Tests;
use Nette;

final class ApiKeyExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->container->getByType(Commands\Create::class, false));

		self::assertNotNull($this->container->getByType(Models\Entities\KeyRepository::class, false));
		self::assertNotNull($this->container->getByType(Models\Entities\KeysManager::class, false));

		self::assertNotNull($this->container->getByType(Middleware\Validator::class, false));
	}

}
