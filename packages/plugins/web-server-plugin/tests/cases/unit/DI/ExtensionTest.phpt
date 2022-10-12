<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\WebServerPlugin\Application;
use FastyBird\WebServerPlugin\Commands;
use FastyBird\WebServerPlugin\Http;
use FastyBird\WebServerPlugin\Middleware;
use FastyBird\WebServerPlugin\Server;
use React\EventLoop;
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
		$container = $this->createContainer();

		Assert::notNull($container->getByType(Application\Console::class));
		Assert::notNull($container->getByType(Application\Application::class));

		Assert::notNull($container->getByType(Commands\HttpServer::class));

		Assert::notNull($container->getByType(Http\ResponseFactory::class));

		Assert::notNull($container->getByType(EventLoop\LoopInterface::class));

		Assert::notNull($container->getByType(Middleware\Cors::class));
		Assert::notNull($container->getByType(Middleware\StaticFiles::class));
		Assert::notNull($container->getByType(Middleware\Router::class));

		Assert::notNull($container->getByType(Server\Factory::class));
	}

}

$test_case = new ExtensionTest();
$test_case->run();
