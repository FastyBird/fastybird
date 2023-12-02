<?php declare(strict_types = 1);

/**
 * Http.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Servers
 * @since          1.0.0
 *
 * @date           19.09.22
 */

namespace FastyBird\Connector\NsPanel\Servers;

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Middleware;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop;
use React\Http as ReactHttp;
use React\Socket;
use Throwable;

/**
 * Connector HTTP communication server
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Servers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Http implements Server
{

	use Nette\SmartObject;

	public const REQUEST_ATTRIBUTE_CONNECTOR = 'connector';

	public const JSON_CONTENT_TYPE = 'application/json';

	private const LISTENING_ADDRESS = '0.0.0.0';

	private Socket\ServerInterface|null $socket = null;

	public function __construct(
		private readonly MetadataDocuments\DevicesModule\Connector $connector,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Middleware\Router $routerMiddleware,
		private readonly NsPanel\Logger $logger,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 */
	public function connect(): void
	{
		try {
			$this->logger->debug(
				'Creating connector web server',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'http-server',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'server' => [
						'address' => self::LISTENING_ADDRESS,
						'port' => $this->connectorHelper->getPort($this->connector),
					],
				],
			);

			$this->socket = new Socket\SocketServer(
				self::LISTENING_ADDRESS . ':' . $this->connectorHelper->getPort($this->connector),
				[],
				$this->eventLoop,
			);
		} catch (Throwable $ex) {
			$this->logger->error(
				'Connector web server could not be created',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'http-server',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			throw new DevicesExceptions\Terminate(
				'Socket server could not be created',
				$ex->getCode(),
				$ex,
			);
		}

		$server = new ReactHttp\HttpServer(
			$this->eventLoop,
			function (ServerRequestInterface $request, callable $next): ResponseInterface {
				$request = $request->withAttribute(
					self::REQUEST_ATTRIBUTE_CONNECTOR,
					$this->connector->getId()->toString(),
				);

				return $next($request);
			},
			$this->routerMiddleware,
		);
		$server->listen($this->socket);

		$server->on('error', function (Throwable $ex): void {
			$this->logger->error(
				'An error occurred during server handling',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'http-server',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			throw new DevicesExceptions\Terminate(
				'HTTP server was terminated',
				$ex->getCode(),
				$ex,
			);
		});
	}

	public function disconnect(): void
	{
		$this->logger->debug(
			'Closing connector web server',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'http-server',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$this->socket?->close();

		$this->socket = null;
	}

}
