<?php declare(strict_types = 1);

/**
 * Factory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Server
 * @since          1.0.0
 *
 * @date           10.06.22
 */

namespace FastyBird\Plugin\WebServer\Server;

use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\WebServer\Middleware;
use Psr\Log;
use React\EventLoop;
use React\Http;
use React\Socket;
use Throwable;
use function sprintf;
use function str_replace;

/**
 * HTTP server factory
 *
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Server
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Factory
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Middleware\Cors $corsMiddleware,
		private readonly Middleware\StaticFiles $staticFilesMiddleware,
		private readonly Middleware\Router $routerMiddleware,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function create(Socket\ServerInterface $server): void
	{
		$httpServer = new Http\HttpServer(
			$this->eventLoop,
			$this->corsMiddleware,
			$this->staticFilesMiddleware,
			$this->routerMiddleware,
		);

		$httpServer->on('error', function (Throwable $ex): void {
			// Log error action reason
			$this->logger->error(
				'An error occurred during handling request. Stopping HTTP server',
				[
					'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WEB_SERVER,
					'type' => 'factory',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$this->eventLoop->stop();
		});

		$httpServer->listen($server);

		if ($server->getAddress() !== null) {
			if ($server instanceof Socket\SecureServer) {
				$this->logger->info(
					sprintf('Listening on "%s"', str_replace('tls:', 'https:', $server->getAddress())),
					[
						'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WEB_SERVER,
						'type' => 'factory',
					],
				);

			} else {
				$this->logger->info(
					sprintf('Listening on "%s"', str_replace('tcp:', 'http:', $server->getAddress())),
					[
						'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WEB_SERVER,
						'type' => 'factory',
					],
				);
			}
		}
	}

}
