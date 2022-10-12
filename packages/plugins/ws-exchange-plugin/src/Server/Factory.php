<?php declare(strict_types = 1);

/**
 * ServerSubscriber.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsExchangePlugin!
 * @subpackage     Server
 * @since          0.1.0
 *
 * @date           21.12.20
 */

namespace FastyBird\WsExchangePlugin\Server;

use FastyBird\Exchange\Consumer as ExchangeConsumer;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\WsExchangePlugin\Consumers;
use IPub\WebSockets;
use Nette\Utils;
use Psr\Log;
use React\Socket;
use Throwable;
use function parse_url;

/**
 * WS server factory
 *
 * @package         FastyBird:WsExchangePlugin!
 * @subpackage      Server
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Factory
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly WebSockets\Server\Handlers $handlers,
		private readonly WebSockets\Server\Configuration $configuration,
		private readonly Consumers\Consumer $exchangeConsumer,
		private readonly ExchangeConsumer\Container $consumer,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function create(Socket\ServerInterface $server): void
	{
		$this->consumer->register($this->exchangeConsumer);

		$server->on('connection', function (Socket\ConnectionInterface $connection): void {
			if ($connection->getLocalAddress() === null) {
				return;
			}

			$parsed = Utils\ArrayHash::from((array) parse_url($connection->getLocalAddress()));

			if (
				$parsed->offsetExists('port')
				&& $parsed->offsetGet('port') === $this->configuration->getPort()
			) {
				$this->handlers->handleConnect($connection);
			}
		});

		$server->on('error', function (Throwable $ex): void {
			$this->logger->error(
				'An error occurred during handling requests. Stopping WS server',
				[
					'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
					'type' => 'factory',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
				],
			);
		});

		$this->logger->debug(
			'Launching WebSockets WS Server',
			[
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
				'type' => 'factory',
				'server' => [
					'address' => $this->configuration->getAddress(),
					'port' => $this->configuration->getPort(),
				],
			],
		);
	}

}
