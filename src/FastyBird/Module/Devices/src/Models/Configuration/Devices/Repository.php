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
 * @date           14.11.23
 */

namespace FastyBird\Module\Devices\Models\Configuration\Devices;

use Contributte\Cache;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use Ramsey\Uuid;
use stdClass;
use Throwable;
use function array_map;
use function assert;
use function is_array;
use function md5;

/**
 * Devices configuration repository
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
		private readonly MetadataDocuments\Mapping\ClassMetadataFactory $classMetadataFactory,
		private readonly MetadataDocuments\DocumentFactory $documentFactory,
	)
	{
		parent::__construct($builder, $cacheFactory);
	}

	/**
	 * @template T of Documents\Devices\Device
	 *
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function find(
		Uuid\UuidInterface $id,
		string $type = Documents\Devices\Device::class,
	): Documents\Devices\Device|null
	{
		$queryObject = new Queries\Configuration\FindDevices();
		$queryObject->byId($id);

		$document = $this->findOneBy($queryObject, $type);

		if ($document !== null && !$document instanceof $type) {
			throw new Exceptions\InvalidState('Could not load document');
		}

		return $document;
	}

	/**
	 * @template T of Documents\Devices\Device
	 *
	 * @param Queries\Configuration\FindDevices<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Configuration\FindDevices $queryObject,
		string $type = Documents\Devices\Device::class,
	): Documents\Devices\Device|null
	{
		try {
			/** @phpstan-var T|null $document */
			$document = $this->cache->load(
				$this->createKeyOne($queryObject) . '_' . md5($type),
				function () use ($queryObject, $type): Documents\Devices\Device|null {
					$space = $this->builder
						->load()
						->find('.' . Devices\Constants::DATA_STORAGE_DEVICES_KEY . '.*');

					$metadata = $this->classMetadataFactory->getMetadataFor($type);

					if ($metadata->getDiscriminatorValue() !== null) {
						$space = $space->find('.[?(@.type =~ /(?i).*^' . $metadata->getDiscriminatorValue() . '*$/)]');
					}

					$result = $queryObject->fetch($space);

					if (!is_array($result) || $result === []) {
						return null;
					}

					$document = $this->documentFactory->create($type, $result[0]);

					if (!$document instanceof $type && !$metadata->isAbstract()) {
						throw new Exceptions\InvalidState('Could not load document');
					}

					return $document;
				},
			);
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Could not load document', $ex->getCode(), $ex);
		}

		return $document;
	}

	/**
	 * @template T of Documents\Devices\Device
	 *
	 * @param Queries\Configuration\FindDevices<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return array<T>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Configuration\FindDevices $queryObject,
		string $type = Documents\Devices\Device::class,
	): array
	{
		try {
			/** @phpstan-var array<T> $documents */
			$documents = $this->cache->load(
				$this->createKeyAll($queryObject) . '_' . md5($type),
				function () use ($queryObject, $type): array {
					$space = $this->builder
						->load()
						->find('.' . Devices\Constants::DATA_STORAGE_DEVICES_KEY . '.*');

					$metadata = $this->classMetadataFactory->getMetadataFor($type);

					if ($metadata->getDiscriminatorValue() !== null) {
						$space = $space->find('.[?(@.type =~ /(?i).*^' . $metadata->getDiscriminatorValue() . '*$/)]');
					}

					$result = $queryObject->fetch($space);

					if (!is_array($result)) {
						return [];
					}

					return array_map(
						fn (stdClass $item): Documents\Devices\Device => $this->documentFactory->create(
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

		return $documents;
	}

}
