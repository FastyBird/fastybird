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
 * @date           16.11.23
 */

namespace FastyBird\Module\Devices\Models\Configuration\Devices\Properties;

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
use function array_filter;
use function array_map;
use function assert;
use function is_array;
use function md5;

/**
 * Devices properties configuration repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Repository extends Models\Configuration\Repository
{

	public function __construct(
		Models\Configuration\Builder $builder,
		Cache\CacheFactory $cacheFactory,
		private readonly MetadataDocuments\DocumentFactory $documentFactory,
	)
	{
		parent::__construct($builder, $cacheFactory);
	}

	/**
	 * @template T of Documents\Devices\Properties\Property
	 *
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function find(
		Uuid\UuidInterface $id,
		string $type = Documents\Devices\Properties\Property::class,
	): Documents\Devices\Properties\Property|null
	{
		$queryObject = new Queries\Configuration\FindDeviceProperties();
		$queryObject->byId($id);

		$document = $this->findOneBy($queryObject, $type);

		if ($document !== null && !$document instanceof $type) {
			throw new Exceptions\InvalidState('Could not load document');
		}

		return $document;
	}

	/**
	 * @template T of Documents\Devices\Properties\Property
	 *
	 * @param Queries\Configuration\FindDeviceProperties<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Configuration\FindDeviceProperties $queryObject,
		string $type = Documents\Devices\Properties\Property::class,
	): Documents\Devices\Properties\Property|null
	{
		try {
			$document = $this->cache->load(
				$this->createKeyOne($queryObject) . '_' . md5($type),
				function () use ($queryObject, $type): Documents\Devices\Properties\Property|false {
					$space = $this->builder
						->load()
						->find('.' . Devices\Constants::DATA_STORAGE_PROPERTIES_KEY . '.*');

					if ($type === Documents\Devices\Properties\Dynamic::class) {
						$space = $space->find('.[?(@.type == "' . Devices\Types\PropertyType::DYNAMIC->value . '")]');

					} elseif ($type === Documents\Devices\Properties\Variable::class) {
						$space = $space->find(
							'.[?(@.type == "' . Devices\Types\PropertyType::VARIABLE->value . '")]',
						);

					} elseif ($type === Documents\Devices\Properties\Mapped::class) {
						$space = $space->find('.[?(@.type == "' . Devices\Types\PropertyType::MAPPED->value . '")]');
					}

					$result = $queryObject->fetch($space);

					if (!is_array($result) || $result === []) {
						return false;
					}

					foreach (
						[
							Documents\Devices\Properties\Dynamic::class,
							Documents\Devices\Properties\Variable::class,
							Documents\Devices\Properties\Mapped::class,
						] as $class
					) {
						try {
							$document = $this->documentFactory->create($class, $result[0]);
							assert($document instanceof $type);

							return $document;
						} catch (Throwable) {
							// Just ignore it
						}
					}

					return false;
				},
			);
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Could not load document', $ex->getCode(), $ex);
		}

		if ($document === false) {
			return null;
		}

		if (!$document instanceof $type) {
			throw new Exceptions\InvalidState('Could not load document');
		}

		return $document;
	}

	/**
	 * @template T of Documents\Devices\Properties\Property
	 *
	 * @param Queries\Configuration\FindDeviceProperties<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return array<T>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Configuration\FindDeviceProperties $queryObject,
		string $type = Documents\Devices\Properties\Property::class,
	): array
	{
		try {
			$documents = $this->cache->load(
				$this->createKeyAll($queryObject) . '_' . md5($type),
				function () use ($queryObject, $type): array {
					$space = $this->builder
						->load()
						->find('.' . Devices\Constants::DATA_STORAGE_PROPERTIES_KEY . '.*');

					if ($type === Documents\Devices\Properties\Dynamic::class) {
						$space = $space->find('.[?(@.type == "' . Devices\Types\PropertyType::DYNAMIC->value . '")]');

					} elseif ($type === Documents\Devices\Properties\Variable::class) {
						$space = $space->find(
							'.[?(@.type == "' . Devices\Types\PropertyType::VARIABLE->value . '")]',
						);

					} elseif ($type === Documents\Devices\Properties\Mapped::class) {
						$space = $space->find('.[?(@.type == "' . Devices\Types\PropertyType::MAPPED->value . '")]');
					}

					$result = $queryObject->fetch($space);

					if (!is_array($result)) {
						return [];
					}

					return array_filter(
						array_map(
							function (stdClass $item): Documents\Devices\Properties\Property|null {
								foreach (
									[
										Documents\Devices\Properties\Dynamic::class,
										Documents\Devices\Properties\Variable::class,
										Documents\Devices\Properties\Mapped::class,
									] as $class
								) {
									try {
										return $this->documentFactory->create($class, $item);
									} catch (Throwable) {
										// Just ignore it
									}
								}

								return null;
							},
							$result,
						),
						static fn ($item): bool => $item instanceof $type,
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
