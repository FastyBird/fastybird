<?php declare(strict_types = 1);

/**
 * StatesManager.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           03.03.20
 */

namespace FastyBird\Plugin\CouchDb\Models;

use Closure;
use Consistence;
use DateTimeInterface;
use FastyBird\Plugin\CouchDb\Connections;
use FastyBird\Plugin\CouchDb\Exceptions;
use FastyBird\Plugin\CouchDb\States;
use Nette;
use Nette\Utils;
use PHPOnCouch;
use Psr\Log;
use Ramsey\Uuid;
use stdClass;
use Throwable;
use function assert;
use function is_numeric;
use function is_object;
use function sprintf;
use const DATE_ATOM;

/**
 * Base properties manager
 *
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @method onAfterCreate(States\IState $state)
 * @method onAfterUpdate(States\IState $state, States\IState $old)
 * @method onAfterDelete(States\IState $state)
 */
class StatesManager implements IStatesManager
{

	use Nette\SmartObject;

	private const MAX_RETRIES = 5;

	/** @var array<Closure> */
	public array $onAfterCreate = [];

	/** @var array<Closure> */
	public array $onAfterUpdate = [];

	/** @var array<Closure> */
	public array $onAfterDelete = [];

	protected Log\LoggerInterface $logger;

	/** @var array<int> */
	private array $retries = [];

	public function __construct(
		private Connections\ICouchDbConnection $dbClient,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function create(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
		string $class = States\State::class,
	): States\IState
	{
		$values->offsetSet('id', $id->toString());

		try {
			$doc = $this->createDoc($values, $class::CREATE_FIELDS);

			$state = States\StateFactory::create($class, $doc);

		} catch (Throwable $ex) {
			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be created', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
				'data' => [
					'state' => $id->toString(),
				],
			]);

			throw new Exceptions\InvalidState('State could not be created', $ex->getCode(), $ex);
		}

		$this->onAfterCreate($state);

		return $state;
	}

	public function update(
		States\IState $state,
		Utils\ArrayHash $values,
	): States\IState
	{
		try {
			$doc = $this->updateDoc($state, $values, $state::UPDATE_FIELDS);

			$updatedState = States\StateFactory::create($state::class, $doc);

		} catch (Exceptions\NotUpdated) {
			return $state;
		} catch (Throwable $ex) {
			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be updated', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
				'data' => [
					'state' => $state->getId()->toString(),
				],
			]);

			throw new Exceptions\InvalidState('State could not be updated', $ex->getCode(), $ex);
		}

		$this->onAfterUpdate($updatedState, $state);

		return $updatedState;
	}

	public function delete(States\IState $state): bool
	{
		$result = $this->deleteDoc($state->getId()
			->toString());

		if ($result === false) {
			return false;
		}

		$this->onAfterDelete($state);

		return true;
	}

	protected function loadDoc(string $id): PHPOnCouch\CouchDocument|null
	{
		try {
			$this->dbClient->getClient()->asCouchDocuments();

			$doc = $this->dbClient->getClient()->getDoc($id);
			assert($doc instanceof PHPOnCouch\CouchDocument);

			return $doc;
		} catch (PHPOnCouch\Exceptions\CouchNotFoundException) {
			return null;
		} catch (Throwable $ex) {
			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be deleted', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
				'document' => [
					'id' => $id,
				],
			]);

			throw new Exceptions\InvalidState('Document could not found.');
		}
	}

	/**
	 * @param array<mixed> $fields
	 */
	protected function createDoc(
		Utils\ArrayHash $values,
		array $fields,
	): PHPOnCouch\CouchDocument
	{
		try {
			// Initialize structure
			$data = new stdClass();

			foreach ($fields as $field => $default) {
				$value = $default;

				if (is_numeric($field)) {
					$field = $default;

					// If default is not defined => field is required
					if (!$values->offsetExists($field)) {
						throw new Exceptions\InvalidArgument(sprintf('Value for key "%s" is required', $field));
					}

					$value = $values->offsetGet($field);

				} elseif ($values->offsetExists($field)) {
					if ($values->offsetGet($field) !== null) {
						$value = $values->offsetGet($field);

						if ($value instanceof DateTimeInterface) {
							$value = $value->format(DATE_ATOM);

						} elseif ($value instanceof Utils\ArrayHash) {
							$value = (array) $value;

						} elseif ($value instanceof Consistence\Enum\Enum) {
							$value = $value->getValue();

						} elseif (is_object($value)) {
							$value = (string) $value;
						}
					} else {
						$value = null;
					}
				}

				$data->{$field} = $value;
			}

			$data->_id = $data->id;

			$this->dbClient->getClient()->storeDoc($data);

			$this->dbClient->getClient()->asCouchDocuments();

			$doc = $this->dbClient->getClient()->getDoc($data->id);
			assert($doc instanceof PHPOnCouch\CouchDocument);

			return $doc;
		} catch (Throwable $ex) {
			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be created', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidState('State document could not be created', $ex->getCode(), $ex);
		}
	}

	/**
	 * @param array<string> $fields
	 */
	protected function updateDoc(
		States\IState $state,
		Utils\ArrayHash $values,
		array $fields,
	): PHPOnCouch\CouchDocument
	{
		$doc = $state->getDocument();

		try {
			$doc->setAutocommit(false);

			$isUpdated = false;

			foreach ($fields as $field) {
				if ($values->offsetExists($field)) {
					$value = $values->offsetGet($field);

					if ($value instanceof DateTimeInterface) {
						$value = $value->format(DATE_ATOM);

					} elseif ($value instanceof Utils\ArrayHash) {
						$value = (array) $value;

					} elseif ($value instanceof Consistence\Enum\Enum) {
						$value = $value->getValue();

					} elseif (is_object($value)) {
						$value = (string) $value;
					}

					if ($doc->get($field) !== $value) {
						$doc->set($field, $value);

						$isUpdated = true;
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

			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be updated', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
				'document' => [
					'id' => $doc->id(),
				],
			]);

			throw new Exceptions\InvalidState('State document could not be updated', $ex->getCode(), $ex);
		} catch (Exceptions\NotUpdated $ex) {
			throw $ex;
		} catch (Throwable $ex) {
			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be updated', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
				'document' => [
					'id' => $doc->id(),
				],
			]);

			throw new Exceptions\InvalidState('State document could not be updated', $ex->getCode(), $ex);
		}
	}

	protected function deleteDoc(string $id): bool
	{
		try {
			$doc = $this->loadDoc($id);

			// Document is already deleted
			if ($doc === null) {
				return true;
			}

			$this->dbClient->getClient()->deleteDoc($doc);

			return true;
		} catch (Throwable $ex) {
			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be deleted', [
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
				'document' => [
					'id' => $id,
				],
			]);
		}

		return false;
	}

}
