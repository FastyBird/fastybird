<?php declare(strict_types = 1);

/**
 * StatesManager.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbPlugin!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           03.03.20
 */

namespace FastyBird\Plugin\CouchDb\Models;

use Consistence;
use DateTimeInterface;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\CouchDb\Connections;
use FastyBird\Plugin\CouchDb\Events;
use FastyBird\Plugin\CouchDb\Exceptions;
use FastyBird\Plugin\CouchDb\States;
use Nette;
use Nette\Utils;
use PHPOnCouch;
use Psr\EventDispatcher;
use Psr\Log;
use Ramsey\Uuid;
use stdClass;
use Throwable;
use function assert;
use function is_numeric;
use function is_object;
use function is_string;
use function property_exists;
use function sprintf;
use function strval;
use const DATE_ATOM;

/**
 * States manager
 *
 * @template T of States\State
 *
 * @package        FastyBird:CouchDbPlugin!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StatesManager
{

	use Nette\SmartObject;

	private const MAX_RETRIES = 5;

	private Log\LoggerInterface $logger;

	/** @var array<int> */
	private array $retries = [];

	/**
	 * @phpstan-param class-string<T> $entity
	 */
	public function __construct(
		private readonly Connections\CouchDbConnection $client,
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
	): States\State
	{
		try {
			$doc = $this->createDoc($id, $values, $this->entity::getCreateFields());

			$state = States\StateFactory::create($this->entity, $doc);

		} catch (Throwable $ex) {
			$this->logger->error('Document could not be created', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_COUCHDB,
				'type' => 'states-manager',
				'group' => 'model',
				'document' => [
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
	): States\State
	{
		try {
			$doc = $this->updateDoc($state, $values, $state::getUpdateFields());

			$updatedState = States\StateFactory::create($state::class, $doc);

		} catch (Exceptions\NotUpdated) {
			return $state;
		} catch (Throwable $ex) {
			$this->logger->error('Document could not be updated', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_COUCHDB,
				'type' => 'states-manager',
				'group' => 'model',
				'document' => [
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
	public function delete(States\State $state): bool
	{
		$result = $this->deleteDoc($state->getId());

		if ($result === false) {
			return false;
		}

		$this->dispatcher?->dispatch(new Events\StateDeleted($state));

		return true;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	protected function loadDoc(string $id): PHPOnCouch\CouchDocument|null
	{
		try {
			$this->client->getClient()->asCouchDocuments();

			$doc = $this->client->getClient()->getDoc($id);
			assert($doc instanceof PHPOnCouch\CouchDocument);

			return $doc;
		} catch (PHPOnCouch\Exceptions\CouchNotFoundException) {
			return null;
		} catch (Throwable $ex) {
			$this->logger->error('State could not be created', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_COUCHDB,
				'type' => 'states-manager',
				'group' => 'model',
				'document' => [
					'id' => $id,
				],
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidState('Document could not found.', $ex->getCode(), $ex);
		}
	}

	/**
	 * @phpstan-param array<string>|array<string, int|string|bool|null> $fields
	 *
	 * @throws Exceptions\InvalidState
	 */
	protected function createDoc(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
		array $fields,
	): PHPOnCouch\CouchDocument
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

				$data->{$field} = $value;
			}

			$data->_id = $data->id;

			$this->client->getClient()->storeDoc($data);

			$this->client->getClient()->asCouchDocuments();

			$doc = $this->client->getClient()->getDoc($data->id);
			assert($doc instanceof PHPOnCouch\CouchDocument);

			return $doc;
		} catch (Throwable $ex) {
			$this->logger->error('Document key could not be created', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_COUCHDB,
				'type' => 'states-manager',
				'group' => 'model',
				'document' => [
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
	protected function updateDoc(
		States\State $state,
		Utils\ArrayHash $values,
		array $fields,
	): PHPOnCouch\CouchDocument
	{
		$doc = $state->getDocument();

		try {
			$doc->setAutocommit(false);

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

					if ($doc->get($field) !== $value) {
						$doc->set($field, $value);

						$isUpdated = true;
					}
				} else {
					if ($field === States\State::UPDATED_AT_FIELD) {
						$doc->set($field, $this->dateTimeFactory->getNow()->format(DATE_ATOM));
					}
				}
			}

			// Commit doc only if is updated
			if (!$isUpdated) {
				throw new Exceptions\NotUpdated('State is not updated');
			}

			// Commit changes into database
			$doc->record();

			unset($this->retries[$doc->id()]);

			return $doc;
		} catch (PHPOnCouch\Exceptions\CouchConflictException $ex) {
			if (
				!isset($this->retries[$doc->id()])
				|| $this->retries[$doc->id()] <= self::MAX_RETRIES
			) {
				if (!isset($this->retries[$doc->id()])) {
					$this->retries[$doc->id()] = 0;
				}

				$this->retries[$doc->id()]++;

				$this->updateDoc($state, $values, $fields);
			}

			$this->logger->error('Document key could not be updated', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_COUCHDB,
				'type' => 'states-manager',
				'group' => 'model',
				'document' => [
					'id' => $state->getId()->toString(),
				],
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidState('State could not be updated', $ex->getCode(), $ex);
		} catch (Exceptions\NotUpdated $ex) {
			throw $ex;
		} catch (Throwable $ex) {
			$this->logger->error('Document key could not be updated', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_COUCHDB,
				'type' => 'states-manager',
				'group' => 'model',
				'document' => [
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

	protected function deleteDoc(Uuid\UuidInterface $id): bool
	{
		try {
			$doc = $this->loadDoc($id->toString());

			// Document is already deleted
			if ($doc === null) {
				return true;
			}

			$this->client->getClient()->deleteDoc($doc);

			return true;
		} catch (Throwable $ex) {
			$this->logger->error('Document could not be deleted', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
				'type' => 'states-manager',
				'group' => 'model',
				'document' => [
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

}
