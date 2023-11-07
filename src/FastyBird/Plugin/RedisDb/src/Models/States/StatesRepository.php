<?php declare(strict_types = 1);

/**
 * StatesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           02.03.20
 */

namespace FastyBird\Plugin\RedisDb\Models\States;

use Clue\React\Redis;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\States;
use FastyBird\Plugin\RedisDb\States\State as T;
use Nette;
use Psr\Log;
use Ramsey\Uuid;
use React\Promise;
use Throwable;
use function assert;
use function is_string;
use function React\Async\await;

/**
 * State repository
 *
 * @template T of States\State
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Models
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StatesRepository
{

	use Nette\SmartObject;

	/**
	 * @param class-string<T> $entity
	 */
	public function __construct(
		private readonly Clients\Client|Redis\RedisClient $client,
		private readonly string $entity = States\State::class,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @phpstan-return T|null
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function findOne(Uuid\UuidInterface $id, int $database = 0): States\State|null
	{
		$raw = $this->getRaw($id, $database);

		if ($raw === null) {
			return null;
		}

		return States\StateFactory::create($this->entity, $raw);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getRaw(Uuid\UuidInterface $id, int $database): string|null
	{
		try {
			$this->client->select($database);

			$getResult = $this->client->get($id->toString());

			if ($getResult instanceof Promise\PromiseInterface) {
				$result = await($getResult);
				assert(is_string($result) || $result === null);

				return $result;
			}

			return $getResult;
		} catch (Throwable $ex) {
			$this->logger->error('Content could not be loaded', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
				'type' => 'state-repository',
				'record' => [
					'id' => $id->toString(),
				],
				'exception' => BootstrapHelpers\Logger::buildException($ex),
			]);

			throw new Exceptions\InvalidState(
				'Content could not be loaded from database: ' . $ex->getMessage(),
				0,
				$ex,
			);
		}
	}

}
