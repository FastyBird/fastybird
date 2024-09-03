<?php declare(strict_types = 1);

/**
 * Http.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Servers
 * @since          1.0.0
 *
 * @date           19.09.22
 */

namespace FastyBird\Connector\HomeKit\Servers;

use Doctrine\DBAL;
use FastyBird\Connector\HomeKit;
use FastyBird\Connector\HomeKit\Clients;
use FastyBird\Connector\HomeKit\Documents;
use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Helpers;
use FastyBird\Connector\HomeKit\Middleware;
use FastyBird\Connector\HomeKit\Protocol;
use FastyBird\Connector\HomeKit\Queue;
use FastyBird\Connector\HomeKit\Subscribers;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette;
use Psr\EventDispatcher as PsrEventDispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop;
use React\Http as ReactHttp;
use React\Socket;
use Throwable;
use TypeError;
use ValueError;
use z4kn4fein\SemVer;
use function hex2bin;
use function is_string;

/**
 * HTTP connector communication server
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Servers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Http implements Server
{

	use Nette\SmartObject;

	public const REQUEST_ATTRIBUTE_CONNECTOR = 'connector';

	public const PAIRING_CONTENT_TYPE = 'application/pairing+tlv8';

	public const JSON_CONTENT_TYPE = 'application/hap+json';

	private const LISTENING_ADDRESS = '0.0.0.0';

	private SecureServer|null $socket = null;

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly Middleware\Router $routerMiddleware,
		private readonly SecureServerFactory $secureServerFactory,
		private readonly Clients\Subscriber $subscriber,
		private readonly Protocol\Loader $accessoriesLoader,
		private readonly Protocol\Driver $accessoriesDriver,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Queue\Queue $queue,
		private readonly HomeKit\Logger $logger,
		private readonly Subscribers\Entities $entitiesSubscriber,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws MetadataExceptions\Mapping
	 * @throws Nette\IOException
	 * @throws SemVer\SemverException
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function initialize(): void
	{
		$this->accessoriesLoader->load($this->connector);

		foreach ($this->accessoriesDriver->getAccessories() as $accessory) {
			if ($accessory instanceof Protocol\Accessories\Generic) {
				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $accessory->getDevice()->getConnector(),
							'device' => $accessory->getDevice()->getId(),
							'state' => DevicesTypes\ConnectionState::CONNECTED,
						],
					),
				);
			}
		}

		$this->entitiesSubscriber->onUpdateSharedKey[] = function (DevicesEntities\Connectors\Properties\Variable $property): void {
			$this->setSharedKey($property);
		};
	}

	public function connect(): void
	{
		try {
			$this->logger->debug(
				'Creating HAP web server',
				[
					'source' => MetadataTypes\Sources\Connector::HOMEKIT->value,
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

			$this->socket = $this->secureServerFactory->create(
				$this->connector,
				new Socket\SocketServer(
					self::LISTENING_ADDRESS . ':' . $this->connectorHelper->getPort($this->connector),
					[],
					$this->eventLoop,
				),
			);
		} catch (Throwable $ex) {
			$this->logger->error(
				'Socket server could not be created',
				[
					'source' => MetadataTypes\Sources\Connector::HOMEKIT->value,
					'type' => 'http-server',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			$this->dispatcher?->dispatch(new DevicesEvents\TerminateConnector(
				MetadataTypes\Sources\Connector::HOMEKIT,
				'Socket server could not be created',
				$ex,
			));

			return;
		}

		$this->socket->on('connection', function (Socket\ConnectionInterface $connection): void {
			$this->logger->debug(
				'New client has connected to server',
				[
					'source' => MetadataTypes\Sources\Connector::HOMEKIT->value,
					'type' => 'http-server',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'client' => [
						'address' => $connection->getRemoteAddress(),
					],
				],
			);

			$this->subscriber->registerConnection($connection);

			$connection->on('close', function () use ($connection): void {
				$this->logger->debug(
					'Connected client has closed connection',
					[
						'source' => MetadataTypes\Sources\Connector::HOMEKIT->value,
						'type' => 'http-server',
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
						'client' => [
							'address' => $connection->getRemoteAddress(),
						],
					],
				);

				$this->subscriber->unregisterConnection($connection);
			});
		});

		$this->socket->on('error', function (Throwable $ex): void {
			$this->logger->error(
				'An error occurred during socket handling',
				[
					'source' => MetadataTypes\Sources\Connector::HOMEKIT->value,
					'type' => 'http-server',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			$this->dispatcher?->dispatch(new DevicesEvents\TerminateConnector(
				MetadataTypes\Sources\Connector::HOMEKIT,
				'HTTP server was terminated',
				$ex,
			));
		});

		$this->socket->on('close', function (): void {
			$this->logger->info(
				'Server was closed',
				[
					'source' => MetadataTypes\Sources\Connector::HOMEKIT->value,
					'type' => 'http-server',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);
		});

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
					'source' => MetadataTypes\Sources\Connector::HOMEKIT->value,
					'type' => 'http-server',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			$this->dispatcher?->dispatch(new DevicesEvents\TerminateConnector(
				MetadataTypes\Sources\Connector::HOMEKIT,
				'HTTP server was terminated',
				$ex,
			));
		});
	}

	public function disconnect(): void
	{
		$this->logger->debug(
			'Closing HAP web server',
			[
				'source' => MetadataTypes\Sources\Connector::HOMEKIT->value,
				'type' => 'http-server',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$this->socket?->close();

		$this->socket = null;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function setSharedKey(
		DevicesEntities\Connectors\Properties\Variable $property,
	): void
	{
		if (
			$property->getConnector()->getId()->equals($this->connector->getId())
			&& $property->getIdentifier() === Types\ConnectorPropertyIdentifier::SHARED_KEY->value
		) {
			$this->logger->debug(
				'Shared key has been updated',
				[
					'source' => MetadataTypes\Sources\Connector::HOMEKIT->value,
					'type' => 'http-server',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			$this->socket?->setSharedKey(
				is_string($property->getValue()) ? (string) hex2bin($property->getValue()) : null,
			);
		}
	}

}
