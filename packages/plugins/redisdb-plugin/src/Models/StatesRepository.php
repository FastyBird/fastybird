<?php declare(strict_types = 1);

/**
 * StatesRepository.php
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
use FastyBird\Metadata\Types;
use FastyBird\RedisDbPlugin\Client;
use FastyBird\RedisDbPlugin\Exceptions;
use FastyBird\RedisDbPlugin\States;
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
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StatesRepository
{

	use Nette\SmartObject;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Client\Client|Redis\Client $client,
		private readonly string $entity = States\State::class,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function findOne(Uuid\UuidInterface $id, int $database = 1): States\State|null
	{
		$raw = $this->getRaw($id, $database);

		if ($raw === null) {
			return null;
		}

		return States\StateFactory::create($this->entity, $raw);
	}

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
				'source' => Types\PluginSource::SOURCE_PLUGIN_REDISDB,
				'type' => 'state-repository',
				'record' => [
					'id' => $id->toString(),
				],
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidState('Content could not be loaded from database', 0, $ex);
		}
	}

}
