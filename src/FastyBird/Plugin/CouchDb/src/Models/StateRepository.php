<?php declare(strict_types = 1);

/**
 * StateRepository.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           02.03.20
 */

namespace FastyBird\Plugin\CouchDb\Models;

use FastyBird\Plugin\CouchDb\Connections;
use FastyBird\Plugin\CouchDb\Exceptions;
use FastyBird\Plugin\CouchDb\States;
use Nette;
use PHPOnCouch;
use Psr\Log;
use Ramsey\Uuid;
use stdClass;
use Throwable;
use function count;
use function is_array;

/**
 * Device property state repository
 *
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StateRepository implements IStateRepository
{

	use Nette\SmartObject;

	private Log\LoggerInterface $logger;

	public function __construct(
		private Connections\ICouchDbConnection $dbClient,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function findOne(
		Uuid\UuidInterface $id,
		string $class = States\State::class,
	): States\IState|null
	{
		$doc = $this->getDocument($id);

		if ($doc === null) {
			return null;
		}

		return States\StateFactory::create($class, $doc);
	}

	private function getDocument(
		Uuid\UuidInterface $id,
	): PHPOnCouch\CouchDocument|null
	{
		try {
			$this->dbClient->getClient()
				->asCouchDocuments();

			/** @var array<stdClass>|mixed $docs */
			$docs = $this->dbClient->getClient()
				->find([
					'id' => [
						'$eq' => $id->toString(),
					],
				]);

			if (is_array($docs) && count($docs) >= 1) {
				$doc = new PHPOnCouch\CouchDocument($this->dbClient->getClient());

				return $doc->loadFromObject($docs[0]);
			}

			return null;
		} catch (PHPOnCouch\Exceptions\CouchNotFoundException) {
			return null;
		} catch (Throwable $ex) {
			$this->logger->error('[FB:PLUGIN:COUCHDB] Document could not be loaded', [
				'type' => 'repository',
				'action' => 'find_document',
				'property' => $id->toString(),
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidState('Document could not be loaded from database', 0, $ex);
		}
	}

}
