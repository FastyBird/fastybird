<?php declare(strict_types = 1);

/**
 * Channel.php
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
use FastyBird\Plugin\RabbitMq\Connections;
use Nette;
use Throwable;
use function assert;
use function is_bool;
use function is_int;

/**
 * Rabbit MQ client
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Channel
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Channel
{

	use Nette\SmartObject;

	private Bunny\Client|null $bunny = null;

	private Bunny\Channel|null $channel = null;

	public function __construct(
		private readonly Connections\Connection $connection,
	)
	{
	}

	/**
	 * @param array<string, string> $headers
	 */
	public function publish(string $body, array $headers, string $exchange, string $routingKey): bool|int
	{
		$bunny = $this->getClient();

		if (!$bunny->isConnected()) {
			try {
				$bunny->connect();
			} catch (Throwable) {
				return false;
			}
		}

		if ($this->channel === null) {
			$channel = $bunny->channel();
			assert($channel instanceof Bunny\Channel);

			$this->channel = $channel;
		}

		$response = $this->channel->publish($body, $headers, $exchange, $routingKey);
		assert(is_bool($response) || is_int($response));

		return $response;
	}

	private function getClient(): Bunny\Client
	{
		if ($this->bunny === null) {
			$this->bunny = new Bunny\Client([
				'host' => $this->connection->getHost(),
				'port' => $this->connection->getPort(),
				'vhost' => $this->connection->getVhost(),
				'user' => $this->connection->getUsername(),
				'password' => $this->connection->getPassword(),
				'heartbeat' => 30,
			]);
		}

		return $this->bunny;
	}

}
