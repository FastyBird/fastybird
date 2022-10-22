<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsExchangePlugin!
 * @subpackage     Controllers
 * @since          0.2.0
 *
 * @date           15.01.22
 */

namespace FastyBird\Plugin\WsExchange\Controllers;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\WsExchange\Events;
use FastyBird\Plugin\WsExchange\Exceptions;
use IPub\WebSockets;
use IPub\WebSocketsWAMP;
use Psr\EventDispatcher;
use Psr\Log;
use function array_key_exists;

/**
 * Exchange sockets controller
 *
 * @package        FastyBird:WsExchangePlugin!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Exchange extends WebSockets\Application\Controller\Controller
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		Log\LoggerInterface|null $logger = null,
	)
	{
		parent::__construct();

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @phpstan-param WebSocketsWAMP\Entities\Topics\ITopic<mixed> $topic
	 */
	public function actionSubscribe(
		WebSocketsWAMP\Entities\Clients\IClient $client,
		WebSocketsWAMP\Entities\Topics\ITopic $topic,
	): void
	{
		$this->logger->debug(
			'Client subscribed to topic',
			[
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
				'type' => 'controller',
				'client' => $client->getId(),
				'topic' => $topic->getId(),
			],
		);

		$this->dispatcher?->dispatch(new Events\ClientSubscribed($client, $topic));
	}

	/**
	 * @phpstan-param Array<string, mixed> $args
	 * @phpstan-param WebSocketsWAMP\Entities\Topics\ITopic<mixed> $topic
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function actionCall(
		array $args,
		WebSocketsWAMP\Entities\Clients\IClient $client,
		WebSocketsWAMP\Entities\Topics\ITopic $topic,
	): void
	{
		$this->logger->debug(
			'Received RPC call from client',
			[
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
				'type' => 'controller',
				'client' => $client->getId(),
				'topic' => $topic->getId(),
				'data' => $args,
			],
		);

		if (!array_key_exists('routing_key', $args) || !array_key_exists('source', $args)) {
			throw new Exceptions\InvalidArgument('Provided message has invalid format');
		}

		$this->dispatcher?->dispatch(new Events\ClientRpc($client, $topic, $args));

		$this->payload->data = [
			'response' => 'accepted',
		];
	}

}
