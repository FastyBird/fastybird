<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\WebServerPlugin\Commands;
use FastyBird\WebServerPlugin\Middleware;
use FastyBird\WebServerPlugin\Server;
use Mockery;
use Ninjify\Nunjuck\TestCase\BaseMockeryTestCase;
use Psr\EventDispatcher;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use React\Socket;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class HttpServerCommandTest extends BaseMockeryTestCase
{

	public function testExecute(): void
	{
		$promise = Mockery::mock(Promise\PromiseInterface::class);
		$promise
			->shouldReceive('then')
			->andReturn($promise);

		$logger = Mockery::mock(Log\LoggerInterface::class);
		$logger
			->shouldReceive('info')
			->withArgs([
				'Launching HTTP Server',
				[
					'source' => 'web-server-plugin',
					'type'   => 'command',
				],
			])
			->times(1)
			->getMock()
			->shouldReceive('info')
			->withArgs([
				'Listening on "http://127.0.0.1:8001"',
				[
					'source' => 'web-server-plugin',
					'type'   => 'factory',
				],
			])
			->times(1);

		$eventLoop = Mockery::mock(EventLoop\LoopInterface::class);
		$eventLoop
			->shouldReceive('addReadStream')
			->getMock()
			->shouldReceive('run')
			->withNoArgs();

		$eventDispatcher = Mockery::mock(EventDispatcher\EventDispatcherInterface::class);
		$eventDispatcher
			->shouldReceive('dispatch')
			->times(1);

		$corsMiddleware = Mockery::mock(Middleware\Cors::class);

		$staticFilesMiddleware = Mockery::mock(Middleware\StaticFiles::class);

		$routerMiddleware = Mockery::mock(Middleware\Router::class);

		$serverFactory = new Server\Factory(
			$corsMiddleware,
			$staticFilesMiddleware,
			$routerMiddleware,
			$eventLoop,
			$logger,
		);

		$application = new Application();
		$application->add(new Commands\HttpServer(
			'127.0.0.1',
			8_001,
			$serverFactory,
			$eventLoop,
			$eventDispatcher,
			$logger
		));

		$command = $application->get(Commands\HttpServer::NAME);

		$commandTester = new CommandTester($command);
		$commandTester->execute([]);

		Assert::true(true);
	}

}

$test_case = new HttpServerCommandTest();
$test_case->run();
