<?php declare(strict_types = 1);

/**
 * StatesManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           03.03.20
 */

namespace FastyBird\Plugin\RedisDb\Models;

use Clue\React\Redis;
use Consistence;
use DateTimeInterface;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RedisDb\Client;
use FastyBird\Plugin\RedisDb\Events;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\States;
use Nette;
use Nette\Utils;
use Psr\EventDispatcher;
use Psr\Log;
use Ramsey\Uuid;
use React\Promise;
use stdClass;
use Throwable;
use function array_keys;
use function assert;
use function get_object_vars;
use function in_array;
use function is_numeric;
use function is_object;
use function is_string;
use function preg_replace;
use function property_exists;
use function React\Async\await;
use function sprintf;
use function strtolower;
use function strval;
use const DATE_ATOM;

/**
 * States manager
 *
 * @template T of States\State
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Models
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StatesManager
{

	use Nette\SmartObject;

	private Log\LoggerInterface $logger;

	/**
	 * @phpstan-param class-string<T> $entity
	 */
	public function __construct(
		private readonly Client\Client|Redis\RedisClient $client,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly string $entity = States\State::class,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @phpstan-return T
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function create(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
		int $database = 0,
	): States\State
	{
		try {
			$raw = $this->createKey($id, $values, $this->entity::getCreateFields(), $database);

			$state = States\StateFactory::create($this->entity, $raw);

		} catch (Throwable $ex) {
			$this->logger->error('Record could not be created', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
				'type' => 'states-manager',
				'record' => [
					'id' => $id->toString(),
				],
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidState('State could not be created', $ex->getCode(), $ex);
		}

		$this->dispatcher?->dispatch(new Events\StateCreated($state));

		return $state;
	}

	/**
	 * @phpstan-param T $state
	 *
	 * @phpstan-return T
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function update(
		States\State $state,
		Utils\ArrayHash $values,
		int $database = 0,
	): States\State
	{
		try {
			$raw = $this->updateKey($state, $values, $state::getUpdateFields(), $database);

			$updatedState = States\StateFactory::create($state::class, $raw);

		} catch (Exceptions\NotUpdated) {
			return $state;
		} catch (Throwable $ex) {
			$this->logger->error('Record could not be updated', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
				'type' => 'states-manager',
				'record' => [
					'id' => $state->getId()->toString(),
				],
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidState('State could not be updated', $ex->getCode(), $ex);
		}

		$this->dispatcher?->dispatch(new Events\StateUpdated($updatedState, $state));

		return $updatedState;
	}

	/**
	 * @phpstan-param T $state
	 */
	public function delete(States\State $state, int $database = 0): bool
	{
		$result = $this->deleteKey($state->getId(), $database);

		if ($result === false) {
			return false;
		}

		$this->dispatcher?->dispatch(new Events\StateDeleted($state));

		return true;
	}

	/**
	 * @phpstan-param array<string>|array<string, int|string|bool|null> $fields
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function createKey(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
		array $fields,
		int $database,
	): string
	{
		try {
			// Initialize structure
			$data = new stdClass();

			$values->offsetSet('id', $id->toString());

			foreach ($fields as $field => $default) {
				$value = $default;

				if (is_numeric($field)) {
					$field = $default;

					// If default is not defined => field is required
					if (!is_string($field) || !property_exists($values, $field)) {
						throw new Exceptions\InvalidArgument(sprintf('Value for key "%s" is required', $field));
					}

					$value = $values->offsetGet($field);

				} elseif (property_exists($values, $field)) {
					if ($values->offsetGet($field) !== null) {
						$value = $values->offsetGet($field);

						if ($value instanceof DateTimeInterface) {
							$value = $value->format(DATE_ATOM);
						} elseif ($value instanceof Utils\ArrayHash) {
							$value = (array) $value;
						} elseif ($value instanceof Consistence\Enum\Enum) {
							$value = $value->getValue();
						} elseif (is_object($value)) {
							$value = strval($value);
						}
					} else {
						$value = null;
					}
				} else {
					if ($field === States\State::CREATED_AT_FIELD) {
						$value = $this->dateTimeFactory->getNow()->format(DATE_ATOM);
					}
				}

				$data->{$this->camelToSnake($field)} = $value;
			}

			$this->client->select($database);
			$setResult = $this->client->set($id->toString(), Utils\Json::encode($data));

			if ($setResult instanceof Promise\PromiseInterface) {
				await($setResult);
			}

			$getResult = $this->client->get($id->toString());

			if ($getResult instanceof Promise\PromiseInterface) {
				$raw = await($getResult);
				assert(is_string($raw) || $raw === null);
			} else {
				$raw = $getResult;
			}

			if ($raw === null) {
				throw new Exceptions\NotUpdated('Created state could not be loaded from database');
			}

			return $raw;
		} catch (Throwable $ex) {
			$this->logger->error('Record key could not be created', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
				'type' => 'states-manager',
				'record' => [
					'id' => $id->toString(),
				],
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidState('State could not be created', $ex->getCode(), $ex);
		}
	}

	/**
	 * @phpstan-param T $state
	 * @phpstan-param array<string> $fields
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function updateKey(
		States\State $state,
		Utils\ArrayHash $values,
		array $fields,
		int $database,
	): string
	{
		$raw = $state->getRaw();

		try {
			$data = Utils\Json::decode($raw);
			assert($data instanceof stdClass);

			$isUpdated = false;

			foreach ($fields as $field) {
				if (property_exists($values, $field)) {
					$value = $values->offsetGet($field);

					if ($value instanceof DateTimeInterface) {
						$value = $value->format(DATE_ATOM);

					} elseif ($value instanceof Utils\ArrayHash) {
						$value = (array) $value;

					} elseif ($value instanceof Consistence\Enum\Enum) {
						$value = $value->getValue();

					} elseif (is_object($value)) {
						$value = strval($value);
					}

					if (
						!in_array($field, array_keys(get_object_vars($data)), true)
						|| $data->{$this->camelToSnake($field)} !== $value
					) {
						$data->{$this->camelToSnake($field)} = $value;

						$isUpdated = true;
					}
				} else {
					if ($field === States\State::UPDATED_AT_FIELD) {
						$data->{$this->camelToSnake($field)} = $this->dateTimeFactory->getNow()->format(DATE_ATOM);
					}
				}
			}

			// Save data only if is updated
			if (!$isUpdated) {
				throw new Exceptions\NotUpdated('Stored state is same as update');
			}

			$this->client->select($database);
			$setResult = $this->client->set($state->getId()->toString(), Utils\Json::encode($data));

			if ($setResult instanceof Promise\PromiseInterface) {
				await($setResult);
			}

			$getResult = $this->client->get($state->getId()->toString());

			if ($getResult instanceof Promise\PromiseInterface) {
				$raw = await($getResult);
				assert(is_string($raw) || $raw === null);
			} else {
				$raw = $getResult;
			}

			if ($raw === null) {
				throw new Exceptions\NotUpdated('Updated state could not be loaded from database');
			}

			return $raw;
		} catch (Exceptions\NotUpdated $ex) {
			throw $ex;
		} catch (Throwable $ex) {
			$this->logger->error('Record key could not be updated', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
				'type' => 'states-manager',
				'record' => [
					'id' => $state->getId()->toString(),
				],
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidState('State could not be updated', $ex->getCode(), $ex);
		}
	}

	private function deleteKey(Uuid\UuidInterface $id, int $database = 0): bool
	{
		try {
			$this->client->select($database);

			$delResult = $this->client->del($id->toString());

			if ($delResult instanceof Promise\PromiseInterface) {
				$result = await($delResult);
				assert(is_numeric($result));

				return $result === 1;
			}

			return $delResult;
		} catch (Throwable $ex) {
			$this->logger->error('Record could not be deleted', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
				'type' => 'states-manager',
				'record' => [
					'id' => $id->toString(),
				],
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);
		}

		return false;
	}

	protected function camelToSnake(string $input): string
	{
		$transformed = preg_replace('/(?<!^)[A-Z]/', '_$0', $input);

		return $transformed !== null ? strtolower($transformed) : $input;
	}

}
