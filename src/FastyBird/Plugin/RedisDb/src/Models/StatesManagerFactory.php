<?php declare(strict_types = 1);

/**
 * StatesManagerFactory.php
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

namespace FastyBird\Plugin\RedisDb\Models;

use Clue\React\Redis;
use FastyBird\Plugin\RedisDb\Client;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\States;
use Nette;
use Psr\EventDispatcher;
use Psr\Log;
use function class_exists;
use function sprintf;

/**
 * States manager factory
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StatesManagerFactory
{

	use Nette\SmartObject;

	private Redis\Client|null $asyncClient = null;

	public function __construct(
		private readonly Client\Client $client,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		private readonly Log\LoggerInterface|null $logger = null,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function create(string $entity = States\State::class): StatesManager
	{
		if (!class_exists($entity)) {
			throw new Exceptions\InvalidArgument(sprintf('Provided entity class %s does not exists', $entity));
		}

		return new StatesManager($this->getClient(), $entity, $this->dispatcher, $this->logger);
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
