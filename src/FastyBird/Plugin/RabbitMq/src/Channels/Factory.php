<?php declare(strict_types = 1);

/**
 * Factory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Channel
 * @since          1.0.0
 *
 * @date           26.03.23
 */

namespace FastyBird\Plugin\RabbitMq\Channels;

use Bunny;
use FastyBird\Library\Exchange\Exchange as ExchangeExchange;
use FastyBird\Library\Metadata\Constants as MetadataConstants;
use FastyBird\Plugin\RabbitMq\Connections;
use FastyBird\Plugin\RabbitMq\Events;
use FastyBird\Plugin\RabbitMq\Exceptions;
use FastyBird\Plugin\RabbitMq\Handlers;
use Nette\Utils;
use Psr\EventDispatcher;
use React\EventLoop;
use React\Promise;

/**
 * RabbitMQ async client factory
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Channel
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Factory implements ExchangeExchange\Factory
{

	private const EXCHANGE_TYPE = 'topic';

	public function __construct(
		private readonly string $exchangeName,
		private readonly Connections\Connection $connection,
		private readonly Handlers\Message $messagesHandler,
		private readonly string|null $queueName = null,
		private readonly EventLoop\LoopInterface|null $eventLoop = null,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	public function create(): void
	{
		$client = new Bunny\Async\Client($this->eventLoop, [
			'host' => $this->connection->getHost(),
			'port' => $this->connection->getPort(),
			'vhost' => $this->connection->getVhost(),
			'user' => $this->connection->getUsername(),
			'password' => $this->connection->getPassword(),
			'heartbeat' => 30,
		]);

		$client
			->connect()
			->then(static fn (Bunny\Async\Client $client) => $client->channel())
			->then(
				static function (Bunny\Channel $channel): Promise\PromiseInterface {
					$qosResult = $channel->qos(0, 5);

					if ($qosResult instanceof Promise\ExtendedPromiseInterface) {
						return $qosResult
							->then(static fn (): Bunny\Channel => $channel);
					}

					throw new Exceptions\InvalidState('RabbitMQ QoS could not be configured');
				},
			)
			->then(
				function (Bunny\Channel $channel): void {
					$this->dispatcher?->dispatch(new Events\ChannelCreated($channel));

					$autoDeleteQueue = false;
					$queueName = $this->queueName;

					if ($queueName === null) {
						$queueName = 'rabbit.plugin_' . Utils\Random::generate();

						$autoDeleteQueue = true;
					}

					// Create exchange
					$channel
						// Try to create exchange
						->exchangeDeclare(
							$this->exchangeName,
							self::EXCHANGE_TYPE,
							false,
							true,
						);

					// Create queue to connect to...
					$channel->queueDeclare(
						$queueName,
						false,
						true,
						false,
						$autoDeleteQueue,
					);

					$channel->queueBind(
						$queueName,
						$this->exchangeName,
						MetadataConstants::MESSAGE_BUS_PREFIX_KEY . '.#',
					);

					$channel->consume(
						function (Bunny\Message $message, Bunny\Channel $channel, Bunny\Async\Client $client): void {
							try {
								$result = $this->messagesHandler->handle($message);
							} catch (Exceptions\Terminate) {
								$client->stop();

								return;
							}

							switch ($result) {
								case Handlers\Message::MESSAGE_ACK:
									$channel->ack($message); // Acknowledge message

									break;
								case Handlers\Message::MESSAGE_NACK:
									$channel->nack($message); // Message will be re-queued

									break;
								case Handlers\Message::MESSAGE_REJECT:
									$channel->reject($message, false); // Message will be discarded

									break;
								case Handlers\Message::MESSAGE_REJECT_AND_TERMINATE:
									$channel->reject($message, false); // Message will be discarded

									$client->stop();

									break;
								default:
									throw new Exceptions\InvalidArgument('Unknown return value of message handler');
							}
						},
						$queueName,
					);
				},
			);
	}

}
