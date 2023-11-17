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

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use Flow\JSONPath;
use stdClass;
use Throwable;
use function array_filter;
use function array_map;
use function implode;
use function is_array;
use function is_string;
use function serialize;

/**
 * Devices properties configuration repository
 *
 * @phpstan-type SupportedClasses MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceVariableProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty
 *
 * @template T of MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceVariableProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty
 * @extends  Models\Configuration\Repository<T|SupportedClasses>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
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
	 * @template Doc of SupportedClasses
	 *
	 * @param Queries\Configuration\FindDeviceProperties<Doc> $queryObject
	 * @param class-string<Doc>|null $type
	 *
	 * @return ($type is class-string<Doc> ? Doc|null : SupportedClasses|null)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function findOneBy(
		Queries\Configuration\FindDeviceProperties $queryObject,
		string|null $type = null,
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	): MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceVariableProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|null
	{
		$document = $this->loadCacheOne(serialize($queryObject->toString() . $type));

		if ($document !== false) {
			return $document;
		}

		try {
			$space = $this->builder
				->load()
				->find('.' . Devices\Constants::DATA_STORAGE_PROPERTIES_KEY . '.*');

			if (is_string($type)) {
				if ($type === MetadataDocuments\DevicesModule\DeviceDynamicProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_DYNAMIC . '")]');

				} elseif ($type === MetadataDocuments\DevicesModule\DeviceVariableProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_VARIABLE . '")]');

				} elseif ($type === MetadataDocuments\DevicesModule\DeviceMappedProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_MAPPED . '")]');
				}
			} else {
				$types = [
					MetadataTypes\PropertyType::TYPE_DYNAMIC,
					MetadataTypes\PropertyType::TYPE_VARIABLE,
					MetadataTypes\PropertyType::TYPE_MAPPED,
				];

				$space = $space->find('.[?(@.type in ["' . implode('","', $types) . '"])]');
			}
		} catch (JSONPath\JSONPathException $ex) {
			throw new Exceptions\InvalidState('', $ex->getCode(), $ex);
		}

		$result = $queryObject->fetch($space);

		if (!is_array($result) || $result === []) {
			$document = null;
		} else {
			if (is_string($type)) {
				$document = $this->entityFactory->create($type, $result[0]);
			} else {
				foreach (
					[
						MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
						MetadataDocuments\DevicesModule\DeviceVariableProperty::class,
						MetadataDocuments\DevicesModule\DeviceMappedProperty::class,
					] as $class
				) {
					try {
						$document = $this->entityFactory->create($class, $result[0]);

						break;
					} catch (Throwable) {
						$document = null;
					}
				}
			}
		}

		$this->writeCacheOne(serialize($queryObject->toString() . $type), $document);

		return $document;
	}

	/**
	 * @template Doc of SupportedClasses
	 *
	 * @param Queries\Configuration\FindDeviceProperties<Doc> $queryObject
	 * @param class-string<Doc>|null $type
	 *
	 * @return ($type is class-string<Doc> ? array<Doc> : array<SupportedClasses>)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Configuration\FindDeviceProperties $queryObject,
		string|null $type = null,
	): array
	{
		$documents = $this->loadCacheAll(serialize($queryObject->toString() . $type));

		if ($documents !== false) {
			return $documents;
		}

		try {
			$space = $this->builder
				->load()
				->find('.' . Devices\Constants::DATA_STORAGE_PROPERTIES_KEY . '.*');

			if (is_string($type)) {
				if ($type === MetadataDocuments\DevicesModule\DeviceDynamicProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_DYNAMIC . '")]');

				} elseif ($type === MetadataDocuments\DevicesModule\DeviceVariableProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_VARIABLE . '")]');

				} elseif ($type === MetadataDocuments\DevicesModule\DeviceMappedProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_MAPPED . '")]');
				}
			} else {
				$types = [
					MetadataTypes\PropertyType::TYPE_DYNAMIC,
					MetadataTypes\PropertyType::TYPE_VARIABLE,
					MetadataTypes\PropertyType::TYPE_MAPPED,
				];

				$space = $space->find('.[?(@.type in ["' . implode('","', $types) . '"])]');
			}
		} catch (JSONPath\JSONPathException $ex) {
			throw new Exceptions\InvalidState('Fetch all data by query failed', $ex->getCode(), $ex);
		}

		$result = $queryObject->fetch($space);

		if (!is_array($result)) {
			return [];
		}

		$documents = array_filter(
			array_map(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				function (stdClass $item) use ($type): MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceVariableProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|null {
					if (is_string($type)) {
						return $this->entityFactory->create($type, $item);
					} else {
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
					}
				},
				$result,
			),
			static fn ($item): bool => $item !== null,
		);

		$this->writeCacheAll(serialize($queryObject->toString() . $type), $documents);

		return $documents;
	}

}
