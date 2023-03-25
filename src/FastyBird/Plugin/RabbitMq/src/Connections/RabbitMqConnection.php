<?php declare(strict_types = 1);

/**
 * RabbitMqConnection.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Connections
 * @since          1.0.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Plugin\RabbitMq\Connections;

use Bunny;
use FastyBird\Plugin\RabbitMq\Exceptions;
use Nette;
use Psr\Log;
use React\EventLoop;
use Throwable;

/**
 * RabbitMQ connection configuration
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Connections
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class RabbitMqConnection implements IRabbitMqConnection
{

	use Nette\SmartObject;

	private Bunny\Client|null $client = null;

	private Bunny\Channel|null $channel = null;

	private Bunny\Async\Client|null $asyncClient = null;

	private Log\LoggerInterface $logger;

	public function __construct(
		Log\LoggerInterface|null $logger = null,
		private EventLoop\LoopInterface|null $eventLoop = null,
		private string $host = '127.0.0.1',
		private int $port = 5672,
		private string $vhost = '/',
		private string $username = 'guest',
		private string $password = 'guest',
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function getAsyncClient(bool $force = false): Bunny\Async\Client
	{
		if ($this->eventLoop === null) {
			throw new Exceptions\InvalidStateException('Event loop is not configured');
		}

		if ($this->asyncClient !== null && !$force) {
			return $this->asyncClient;
		}

		$this->asyncClient = new Bunny\Async\Client($this->eventLoop, [
			'host' => $this->getHost(),
			'port' => $this->getPort(),
			'vhost' => $this->getVhost(),
			'user' => $this->getUsername(),
			'password' => $this->getPassword(),
			'heartbeat' => 30,
		]);

		return $this->asyncClient;
	}

	public function getChannel(): Bunny\Channel
	{
		if ($this->channel === null) {
			$this->channel = $this->createChannel();
		}

		return $this->channel;
	}

	public function setChannel(Bunny\Channel $channel): void
	{
		$this->channel = $channel;
	}

	private function createChannel(): Bunny\Channel
	{
		if ($this->channel !== null) {
			return $this->channel;
		}

		$bunny = $this->getClient();

		$channel = null;

		try {
			$channel = $bunny->channel();

			if (!$channel instanceof Bunny\Channel) {
				throw new Exceptions\InvalidStateException('Bunny channel could not be opened');
			}
		} catch (Bunny\Exception\ClientException $ex) {
			if ($ex->getMessage() !== 'Broken pipe or closed connection.') {
				throw new Exceptions\InvalidStateException($ex->getMessage(), $ex->getCode(), $ex);
			}

			/**
			 * Try to reconnect
			 */
			$bunny = $this->getClient(true);

			$channel = $bunny->channel();

			if (!$channel instanceof Bunny\Channel) {
				throw new Exceptions\InvalidStateException('Bunny channel could not be opened');
			}
		}

		return $channel;
	}

	public function getClient(bool $force = false): Bunny\Client
	{
		if ($this->client !== null && !$force) {
			return $this->client;
		}

		// Create bunny
		$this->client = new Bunny\Client([
			'host' => $this->getHost(),
			'port' => $this->getPort(),
			'vhost' => $this->getVhost(),
			'user' => $this->getUsername(),
			'password' => $this->getPassword(),
			'heartbeat' => 30,
		]);

		try {
			$this->client->connect();

			return $this->client;
		} catch (Throwable $ex) {
			// Log error action reason
			$this->logger->error('[FB:PLUGIN:RABBITMQ] Could not connect to bunny', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidStateException('Connection could not be established', 0, $ex);
		}
	}

	public function getHost(): string
	{
		return $this->host;
	}

	public function getPort(): int
	{
		return $this->port;
	}

	public function getVhost(): string
	{
		return $this->vhost;
	}

	public function getUsername(): string
	{
		return $this->username;
	}

	public function getPassword(): string
	{
		return $this->password;
	}

}
