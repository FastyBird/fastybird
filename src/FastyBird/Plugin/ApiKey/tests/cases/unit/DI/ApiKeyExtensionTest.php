<?php declare(strict_types = 1);

namespace FastyBird\Plugin\ApiKey\Tests\Cases\Unit\DI;

use FastyBird\Plugin\ApiKey\Commands;
use FastyBird\Plugin\ApiKey\Middleware;
use FastyBird\Plugin\ApiKey\Models;
use FastyBird\Plugin\ApiKey\Tests\Cases\Unit\BaseTestCase;
use Nette;

final class ApiKeyExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function XtestServicesRegistration(): void
	{
		self::assertNotNull($this->container->getByType(Commands\Create::class, false));

		self::assertNotNull($this->container->getByType(Models\KeyRepository::class, false));
		self::assertNotNull($this->container->getByType(Models\KeysManager::class, false));

		self::assertNotNull($this->container->getByType(Middleware\Validator::class, false));
	}

}
