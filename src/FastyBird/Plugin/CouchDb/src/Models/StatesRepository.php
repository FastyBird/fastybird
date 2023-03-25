<?php declare(strict_types = 1);

/**
 * StatesRepository.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbPlugin!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           02.03.20
 */

namespace FastyBird\Plugin\CouchDb\Models;

use FastyBird\Library\Metadata\Types as MetadataTypes;
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
 * State repository
 *
 * @template T of States\State
 *
 * @package        FastyBird:CouchDbPlugin!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StatesRepository
{

	use Nette\SmartObject;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Connections\Connection $client,
		private readonly string $entity = States\State::class,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @phpstan-return T|null
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function findOne(Uuid\UuidInterface $id): States\State|null
	{
		$doc = $this->getDocument($id);

		if ($doc === null) {
			return null;
		}

		return States\StateFactory::create($this->entity, $doc);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getDocument(
		Uuid\UuidInterface $id,
	): PHPOnCouch\CouchDocument|null
	{
		try {
			$this->client->getClient()->asCouchDocuments();

			/** @var array<stdClass>|mixed $docs */
			$docs = $this->client->getClient()
				->find([
					'id' => [
						'$eq' => $id->toString(),
					],
				]);

			if (is_array($docs) && count($docs) >= 1) {
				$doc = new PHPOnCouch\CouchDocument($this->client->getClient());

				return $doc->loadFromObject($docs[0]);
			}

			return null;
		} catch (PHPOnCouch\Exceptions\CouchNotFoundException) {
			return null;
		} catch (Throwable $ex) {
			$this->logger->error('Content could not be loaded', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_COUCHDB,
				'type' => 'state-repository',
				'group' => 'model',
				'record' => [
					'id' => $id->toString(),
				],
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidState('Content could not be loaded from database' . $ex->getMessage(), 0, $ex);
		}
	}

}
