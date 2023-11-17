<?php declare(strict_types = 1);

/**
 * Repository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           13.11.23
 */

namespace FastyBird\Module\Devices\Models\Configuration\Connectors;

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use Flow\JSONPath;
use stdClass;
use function array_map;
use function is_array;

/**
 * Connectors configuration repository
 *
 * @template T of MetadataDocuments\DevicesModule\Connector
 * @extends  Models\Configuration\Repository<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Repository extends Models\Configuration\Repository
{

	public function __construct(
		private readonly Models\Configuration\Builder $builder,
		private readonly MetadataDocuments\DocumentFactory $entityFactory,
	)
	{
	}

	/**
	 * @param Queries\Configuration\FindConnectors<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function findOneBy(
		Queries\Configuration\FindConnectors $queryObject,
		string $type = MetadataDocuments\DevicesModule\Connector::class,
	): MetadataDocuments\DevicesModule\Connector|null
	{
		$document = $this->loadCacheOne(serialize($queryObject->toString() . $type));

		if ($document !== false) {
			return $document;
		}

		try {
			$space = $this->builder
				->load()
				->find('.' . Devices\Constants::DATA_STORAGE_CONNECTORS_KEY . '.*');
		} catch (JSONPath\JSONPathException $ex) {
			throw new Exceptions\InvalidState('', $ex->getCode(), $ex);
		}

		$result = $queryObject->fetch($space);

		if (!is_array($result) || $result === []) {
			return null;
		}

		$document = $this->entityFactory->create($type, $result[0]);

		$this->writeCacheOne(serialize($queryObject->toString() . $type), $document);

		return $document;
	}

	/**
	 * @param Queries\Configuration\FindConnectors<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return array<T>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Configuration\FindConnectors $queryObject,
		string $type = MetadataDocuments\DevicesModule\Connector::class,
	): array
	{
		$documents = $this->loadCacheAll(serialize($queryObject->toString() . $type));

		if ($documents !== false) {
			return $documents;
		}

		try {
			$space = $this->builder
				->load()
				->find('.' . Devices\Constants::DATA_STORAGE_CONNECTORS_KEY . '.*');
		} catch (JSONPath\JSONPathException $ex) {
			throw new Exceptions\InvalidState('Fetch all data by query failed', $ex->getCode(), $ex);
		}

		$result = $queryObject->fetch($space);

		if (!is_array($result)) {
			return [];
		}

		$documents = array_map(
			fn (stdClass $item): MetadataDocuments\DevicesModule\Connector => $this->entityFactory->create(
				$type,
				$item,
			),
			$result,
		);

		$this->writeCacheAll(serialize($queryObject->toString() . $type), $documents);

		return $documents;
	}

}
