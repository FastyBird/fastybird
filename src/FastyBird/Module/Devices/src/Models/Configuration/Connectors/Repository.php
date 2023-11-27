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

use Contributte\Cache;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use Nette\Caching;
use stdClass;
use Throwable;
use function array_map;
use function is_array;
use function md5;

/**
 * Connectors configuration repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Repository extends Models\Configuration\Repository
{

	public function __construct(
		Models\Configuration\Builder $builder,
		Cache\CacheFactory $cacheFactory,
		private readonly MetadataDocuments\DocumentFactory $entityFactory,
	)
	{
		parent::__construct($builder, $cacheFactory);
	}

	/**
	 * @template T of MetadataDocuments\DevicesModule\Connector
	 *
	 * @param Queries\Configuration\FindConnectors<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Configuration\FindConnectors $queryObject,
		string $type = MetadataDocuments\DevicesModule\Connector::class,
	): MetadataDocuments\DevicesModule\Connector|null
	{
		try {
			$document = $this->cache->load(
				$this->createKeyOne($queryObject) . '_' . md5($type),
				function () use ($queryObject, $type, &$dependencies): MetadataDocuments\DevicesModule\Connector|null {
					$dependencies[Caching\Cache::Files] = $this->builder->getConfigurationFile();

					$space = $this->builder
						->load()
						->find('.' . Devices\Constants::DATA_STORAGE_CONNECTORS_KEY . '.*');

					$result = $queryObject->fetch($space);

					if (!is_array($result) || $result === []) {
						return null;
					}

					return $this->entityFactory->create($type, $result[0]);
				},
			);
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Could not load document', $ex->getCode(), $ex);
		}

		if ($document !== null && !$document instanceof $type) {
			throw new Exceptions\InvalidState('Could not load document');
		}

		return $document;
	}

	/**
	 * @template T of MetadataDocuments\DevicesModule\Connector
	 *
	 * @param Queries\Configuration\FindConnectors<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return array<T>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Configuration\FindConnectors $queryObject,
		string $type = MetadataDocuments\DevicesModule\Connector::class,
	): array
	{
		try {
			$documents = $this->cache->load(
				$this->createKeyAll($queryObject) . '_' . md5($type),
				function () use ($queryObject, $type, &$dependencies): array {
					$dependencies[Caching\Cache::Files] = $this->builder->getConfigurationFile();

					$space = $this->builder
						->load()
						->find('.' . Devices\Constants::DATA_STORAGE_CONNECTORS_KEY . '.*');

					$result = $queryObject->fetch($space);

					if (!is_array($result)) {
						return [];
					}

					return array_map(
						fn (stdClass $item): MetadataDocuments\DevicesModule\Connector => $this->entityFactory->create(
							$type,
							$item,
						),
						$result,
					);
				},
			);
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Could not load documents', $ex->getCode(), $ex);
		}

		if (!is_array($documents)) {
			throw new Exceptions\InvalidState('Could not load documents');
		}

		return $documents;
	}

}
