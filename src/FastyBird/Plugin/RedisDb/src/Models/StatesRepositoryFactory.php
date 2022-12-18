<?php declare(strict_types = 1);

/**
 * StatesRepositoryFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           02.03.20
 */

namespace FastyBird\Plugin\RedisDb\Models;

use Clue\React\Redis;
use FastyBird\Plugin\RedisDb\Client;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\States;
use Nette;
use Psr\Log;
use function class_exists;
use function sprintf;

/**
 * State repository factory
 *
 * @template T of States\State
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Models
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StatesRepositoryFactory
{

	use Nette\SmartObject;

	private Redis\RedisClient|null $asyncClient = null;

	public function __construct(
		private readonly Client\Client $client,
		private readonly Log\LoggerInterface|null $logger = null,
	)
	{
	}

	/**
	 * @phpstan-param class-string<T> $entity
	 *
	 * @phpstan-return StatesRepository<T>
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function create(string $entity = States\State::class): StatesRepository
	{
		if (!class_exists($entity)) {
			throw new Exceptions\InvalidArgument(sprintf('Provided entity class %s does not exists', $entity));
		}

		return new StatesRepository($this->getClient(), $entity, $this->logger);
	}

	/**
	 * @internal
	 */
	public function setAsyncClient(Redis\RedisClient $client): void
	{
		$this->asyncClient = $client;
	}

	private function getClient(): Client\Client|Redis\RedisClient
	{
		if ($this->asyncClient !== null) {
			return $this->asyncClient;
		}

		return $this->client;
	}

}
