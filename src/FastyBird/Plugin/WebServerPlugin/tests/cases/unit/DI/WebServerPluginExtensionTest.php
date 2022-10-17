<?php declare(strict_types = 1);

namespace FastyBird\WebServerPlugin\Tests\Cases\Unit\DI;

use FastyBird\WebServerPlugin\Application;
use FastyBird\WebServerPlugin\Commands;
use FastyBird\WebServerPlugin\Http;
use FastyBird\WebServerPlugin\Middleware;
use FastyBird\WebServerPlugin\Server;
use FastyBird\WebServerPlugin\Tests\Cases\Unit\BaseTestCase;
use Nette;
use React\EventLoop;

final class WebServerPluginExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testCompilersServices(): void
	{
		self::assertNotNull($this->container->getByType(Application\Console::class, false));
		self::assertNotNull($this->container->getByType(Application\Application::class, false));

		self::assertNotNull($this->container->getByType(Commands\HttpServer::class, false));

		self::assertNotNull($this->container->getByType(Http\ResponseFactory::class, false));

		self::assertNotNull($this->container->getByType(EventLoop\LoopInterface::class, false));

		self::assertNotNull($this->container->getByType(Middleware\Cors::class, false));
		self::assertNotNull($this->container->getByType(Middleware\StaticFiles::class, false));
		self::assertNotNull($this->container->getByType(Middleware\Router::class, false));

		self::assertNotNull($this->container->getByType(Server\Factory::class, false));
	}

}
