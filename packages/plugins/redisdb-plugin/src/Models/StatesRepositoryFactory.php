<?php declare(strict_types = 1);

/**
 * StatesRepositoryFactory.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           02.03.20
 */

namespace FastyBird\RedisDbPlugin\Models;

use Clue\React\Redis;
use FastyBird\RedisDbPlugin\Client;
use FastyBird\RedisDbPlugin\Exceptions;
use FastyBird\RedisDbPlugin\States;
use Nette;
use Psr\Log;
use function class_exists;
use function sprintf;

/**
 * State repository factory
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StatesRepositoryFactory
{

	use Nette\SmartObject;

	private Redis\Client|null $asyncClient = null;

	public function __construct(
		private readonly Client\Client $client,
		private readonly Log\LoggerInterface|null $logger = null,
	)
	{
	}

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
	public function setAsyncClient(Redis\Client $client): void
	{
		$this->asyncClient = $client;
	}

	private function getClient(): Client\Client|Redis\Client
	{
		if ($this->asyncClient !== null) {
			return $this->asyncClient;
		}

		return $this->client;
	}

}
