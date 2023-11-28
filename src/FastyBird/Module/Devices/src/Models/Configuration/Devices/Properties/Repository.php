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
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
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
		private readonly MetadataDocuments\DocumentFactory $entityFactory,
	)
	{
		parent::__construct($builder, $cacheFactory);
	}

	/**
	 * @template T of MetadataDocuments\DevicesModule\DeviceProperty
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
		string $type = MetadataDocuments\DevicesModule\DeviceProperty::class,
	): MetadataDocuments\DevicesModule\DeviceProperty|null
	{
		try {
			$document = $this->cache->load(
				$this->createKeyOne($queryObject) . '_' . md5($type),
				function () use ($queryObject, $type): MetadataDocuments\DevicesModule\DeviceProperty|false {
					$space = $this->builder
						->load()
						->find('.' . Devices\Constants::DATA_STORAGE_PROPERTIES_KEY . '.*');

					if ($type === MetadataDocuments\DevicesModule\DeviceDynamicProperty::class) {
						$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_DYNAMIC . '")]');

					} elseif ($type === MetadataDocuments\DevicesModule\DeviceVariableProperty::class) {
						$space = $space->find(
							'.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_VARIABLE . '")]',
						);

					} elseif ($type === MetadataDocuments\DevicesModule\DeviceMappedProperty::class) {
						$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_MAPPED . '")]');
					}

					$result = $queryObject->fetch($space);

					if (!is_array($result) || $result === []) {
						return false;
					}

					foreach (
						[
							MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
							MetadataDocuments\DevicesModule\DeviceVariableProperty::class,
							MetadataDocuments\DevicesModule\DeviceMappedProperty::class,
						] as $class
					) {
						try {
							$document = $this->entityFactory->create($class, $result[0]);
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
	 * @template T of MetadataDocuments\DevicesModule\DeviceProperty
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
		string $type = MetadataDocuments\DevicesModule\DeviceProperty::class,
	): array
	{
		try {
			$documents = $this->cache->load(
				$this->createKeyAll($queryObject) . '_' . md5($type),
				function () use ($queryObject, $type): array {
					$space = $this->builder
						->load()
						->find('.' . Devices\Constants::DATA_STORAGE_PROPERTIES_KEY . '.*');

					if ($type === MetadataDocuments\DevicesModule\DeviceDynamicProperty::class) {
						$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_DYNAMIC . '")]');

					} elseif ($type === MetadataDocuments\DevicesModule\DeviceVariableProperty::class) {
						$space = $space->find(
							'.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_VARIABLE . '")]',
						);

					} elseif ($type === MetadataDocuments\DevicesModule\DeviceMappedProperty::class) {
						$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_MAPPED . '")]');
					}

					$result = $queryObject->fetch($space);

					if (!is_array($result)) {
						return [];
					}

					return array_filter(
						array_map(
						// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
							function (stdClass $item): MetadataDocuments\DevicesModule\DeviceProperty|null {
								foreach (
									[
										MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
										MetadataDocuments\DevicesModule\DeviceVariableProperty::class,
										MetadataDocuments\DevicesModule\DeviceMappedProperty::class,
									] as $class
								) {
									try {
										return $this->entityFactory->create($class, $item);
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
