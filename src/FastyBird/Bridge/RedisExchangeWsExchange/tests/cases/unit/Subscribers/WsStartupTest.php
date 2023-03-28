<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisExchangeWsExchange\Tests\Cases\Unit\Subscribers;

use FastyBird\Bridge\RedisExchangeWsExchange\Subscribers;
use FastyBird\Bridge\RedisExchangeWsExchange\Tests\Cases\Unit;
use FastyBird\Plugin\RedisDb\Clients as RedisDbClient;
use FastyBird\Plugin\WsExchange\Commands as WsExchangeCommands;
use FastyBird\Plugin\WsExchange\Server as WsExchangeServer;
use IPub\WebSockets;
use Nette;
use Psr\EventDispatcher;
use React\EventLoop;
use Symfony\Component\Console;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use function method_exists;

final class WsStartupTest extends Unit\BaseTestCase
{

	/**
	 * @throws Console\Exception\LogicException
	 * @throws Console\Exception\CommandNotFoundException
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testExecute(): void
	{
		$eventLoop = $this->createMock(EventLoop\LoopInterface::class);

		$this->mockContainerService(EventLoop\LoopInterface::class, $eventLoop);

		$redisFactory = $this->createMock(RedisDbClient\Factory::class);
		$redisFactory
			->expects(self::once())
			->method('create');

		$this->mockContainerService(RedisDbClient\Factory::class, $redisFactory);

		$dispatcher = $this->container->getByType(EventDispatcher\EventDispatcherInterface::class);

		if (method_exists($dispatcher, 'addSubscriber')) {
			$dispatcher->addSubscriber($this->container->getByType(Subscribers\WsServer::class));
		}

		$application = new Application();
		$application->add(new WsExchangeCommands\WsServer(
			$this->container->getByType(WebSockets\Server\Configuration::class),
			$this->container->getByType(WsExchangeServer\Factory::class),
			$this->container->getByType(EventLoop\LoopInterface::class),
			$this->container->getByType(EventDispatcher\EventDispatcherInterface::class),
		));

		$command = $application->get(WsExchangeCommands\WsServer::NAME);

		$commandTester = new CommandTester($command);
		$commandTester->execute([]);
	}

}
