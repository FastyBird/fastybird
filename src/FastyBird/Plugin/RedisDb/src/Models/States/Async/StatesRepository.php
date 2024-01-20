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

namespace FastyBird\Plugin\RedisDb\Models\States\Async;

use FastyBird\Library\Application\Helpers as ApplicationHelpers;
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

/**
 * Asynchronous state repository
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
		private readonly Clients\Async\Client $client,
		private readonly States\StateFactory $stateFactory,
		private readonly string $entity = States\State::class,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<T|null>
	 */
	public function find(Uuid\UuidInterface $id, int $database = 0): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->getRaw($id, $database)
			->then(function (string|null $raw) use ($deferred): void {
				if ($raw === null) {
					$deferred->resolve(null);

					return;
				}

				$state = $this->stateFactory->create($this->entity, $raw);

				$deferred->resolve($state);
			})
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<string|null>
	 */
	private function getRaw(Uuid\UuidInterface $id, int $database): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->client->select($database);
		$this->client->get($id->toString())
			->then(static function (string|null $result) use ($deferred): void {
				$deferred->resolve($result);
			})
			->catch(function (Throwable $ex) use ($id, $deferred): void {
				$this->logger->error('Content could not be loaded', [
					'source' => MetadataTypes\PluginSource::REDISDB,
					'type' => 'state-async-repository',
					'record' => [
						'id' => $id->toString(),
					],
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				]);

				$deferred->reject(
					new Exceptions\InvalidState(
						'Content could not be loaded from database: ' . $ex->getMessage(),
						0,
						$ex,
					),
				);
			});

		return $deferred->promise();
	}

}
