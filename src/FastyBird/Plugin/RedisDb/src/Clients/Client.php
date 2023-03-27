<?php declare(strict_types = 1);

/**
 * Client.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Client
 * @since          0.1.0
 *
 * @date           17.09.21
 */

namespace FastyBird\Plugin\RedisDb\Clients;

use FastyBird\Plugin\RedisDb\Connections;
use Nette;
use Predis;
use Predis\Response as PredisResponse;
use function assert;
use function is_int;

/**
 * Redis DB proxy client to PREDIS instance
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Client
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Client
{

	use Nette\SmartObject;

	/** @var Predis\Client<mixed>|null */
	private Predis\Client|null $redis = null;

	/** @var array<string, int|string|null> */
	private array $options;

	public function __construct(Connections\Connection $connection)
	{
		$this->options = [
			'scheme' => 'tcp',
			'host' => $connection->getHost(),
			'port' => $connection->getPort(),
		];

		if ($connection->getUsername() !== null) {
			$this->options['username'] = $connection->getUsername();
		}

		if ($connection->getPassword() !== null) {
			$this->options['password'] = $connection->getPassword();
		}
	}

	public function set(string $key, string $content): bool
	{
		$response = $this->getClient()->set($key, $content);
		assert($response instanceof PredisResponse\Status);

		return $response->getPayload() === 'OK';
	}

	public function get(string $key): string|null
	{
		return $this->getClient()->get($key);
	}

	public function del(string $key): bool
	{
		if ($this->getClient()->get($key) !== null) {
			$response = $this->getClient()->del($key);

			return $response === 1;
		}

		return true;
	}

	public function publish(string $channel, string $content): bool
	{
		$client = $this->getClient();

		$response = $client->executeCommand($client->createCommand('publish', [$channel, $content]));
		assert(is_int($response) || $response instanceof PredisResponse\ResponseInterface);

		return !$response instanceof PredisResponse\ErrorInterface;
	}

	public function select(int $database): void
	{
		$this->getClient()->select($database);
	}

	private function getClient(): Predis\Client
	{
		if ($this->redis === null) {
			$this->redis = new Predis\Client($this->options);
		}

		return $this->redis;
	}

}
