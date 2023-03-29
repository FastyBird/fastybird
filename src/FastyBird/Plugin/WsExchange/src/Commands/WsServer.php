<?php declare(strict_types = 1);

/**
 * WsServer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsExchangePlugin!
 * @subpackage     Commands
 * @since          0.13.0
 *
 * @date           09.06.22
 */

namespace FastyBird\Plugin\WsExchange\Commands;

use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Exchange\Exchange as ExchangeExchange;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\WsExchange\Consumers;
use FastyBird\Plugin\WsExchange\Events;
use FastyBird\Plugin\WsExchange\Server;
use IPub\WebSockets;
use Nette;
use Psr\EventDispatcher;
use Psr\Log;
use React\EventLoop;
use React\Socket;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Throwable;

/**
 * WS server command
 *
 * @package        FastyBird:WsExchangePlugin!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WsServer extends Console\Command\Command
{

	use Nette\SmartObject;

	public const NAME = 'fb:ws-exchange:start';

	private Log\LoggerInterface $logger;

	/**
	 * @param array<ExchangeExchange\Factory> $exchangeFactories
	 */
	public function __construct(
		private readonly WebSockets\Server\Configuration $configuration,
		private readonly Server\Factory $serverFactory,
		private readonly ExchangeConsumers\Container $consumer,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly array $exchangeFactories = [],
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		Log\LoggerInterface|null $logger = null,
		string|null $name = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();

		parent::__construct($name);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function configure(): void
	{
		parent::configure();

		$this
			->setName(self::NAME)
			->setDescription('WebSockets server service');
	}

	protected function execute(
		Input\InputInterface $input,
		Output\OutputInterface $output,
	): int
	{
		$this->logger->info(
			'Starting WS server',
			[
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
				'type' => 'server-command',
				'group' => 'cmd',
			],
		);

		try {
			$this->dispatcher?->dispatch(new Events\Startup());

			$socketServer = new Socket\SocketServer(
				$this->configuration->getAddress() . ':' . $this->configuration->getPort(),
				[],
				$this->eventLoop,
			);

			$socketServer->on('error', function (Throwable $ex): void {
				$this->dispatcher?->dispatch(new Events\Error($ex));
			});

			$this->consumer->enable(Consumers\Consumer::class);

			$this->serverFactory->create($socketServer);

			foreach ($this->exchangeFactories as $exchangeFactory) {
				$exchangeFactory->create();
			}

			$this->eventLoop->run();

		} catch (WebSockets\Exceptions\TerminateException $ex) {
			// Log error action reason
			$this->logger->error(
				'WS server was forced to close',
				[
					'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
					'type' => 'server-command',
					'group' => 'cmd',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'cmd' => $this->getName(),
				],
			);

			$this->eventLoop->stop();

		} catch (Throwable $ex) {
			// Log error action reason
			$this->logger->error(
				'An unhandled error occurred. Stopping WS server',
				[
					'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
					'type' => 'server-command',
					'group' => 'cmd',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'cmd' => $this->getName(),
				],
			);

			$this->eventLoop->stop();

			return self::FAILURE;
		}

		return self::SUCCESS;
	}

}
