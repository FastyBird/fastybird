<?php declare(strict_types = 1);

namespace FastyBird\Plugin\WebServer\Tests\Cases\Unit\DI;

use FastyBird\Plugin\WebServer\Application;
use FastyBird\Plugin\WebServer\Commands;
use FastyBird\Plugin\WebServer\Http;
use FastyBird\Plugin\WebServer\Middleware;
use FastyBird\Plugin\WebServer\Server;
use FastyBird\Plugin\WebServer\Subscribers;
use FastyBird\Plugin\WebServer\Tests;
use Nette;
use React\EventLoop;

final class WebServerExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testCompilersServices(): void
	{
		self::assertNotNull($this->container->getByType(Application\Application::class, false));

		self::assertNotNull($this->container->getByType(Commands\HttpServer::class, false));

		self::assertNotNull($this->container->getByType(Http\ResponseFactory::class, false));

		self::assertNotNull($this->container->getByType(EventLoop\LoopInterface::class, false));

		self::assertNotNull($this->container->getByType(Middleware\Cors::class, false));
		self::assertNotNull($this->container->getByType(Middleware\StaticFiles::class, false));
		self::assertNotNull($this->container->getByType(Middleware\Router::class, false));

		self::assertNotNull($this->container->getByType(Server\Factory::class, false));

		self::assertNotNull($this->container->getByType(Subscribers\Server::class, false));
	}

}
