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

namespace FastyBird\Module\Devices\Models\Configuration\Connectors\Properties;

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
 * Connectors properties configuration repository
 *
 * @phpstan-type SupportedClasses MetadataDocuments\DevicesModule\ConnectorDynamicProperty|MetadataDocuments\DevicesModule\ConnectorVariableProperty
 *
 * @template T of MetadataDocuments\DevicesModule\ConnectorDynamicProperty|MetadataDocuments\DevicesModule\ConnectorVariableProperty
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
	 * @param Queries\Configuration\FindConnectorProperties<Doc> $queryObject
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
		Queries\Configuration\FindConnectorProperties $queryObject,
		string|null $type = null,
	): MetadataDocuments\DevicesModule\ConnectorDynamicProperty|MetadataDocuments\DevicesModule\ConnectorVariableProperty|null
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
				if ($type === MetadataDocuments\DevicesModule\ConnectorDynamicProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_DYNAMIC . '")]');

				} elseif ($type === MetadataDocuments\DevicesModule\ConnectorVariableProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_VARIABLE . '")]');
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
						MetadataDocuments\DevicesModule\ConnectorDynamicProperty::class,
						MetadataDocuments\DevicesModule\ConnectorVariableProperty::class,
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
	 * @param Queries\Configuration\FindConnectorProperties<Doc> $queryObject
	 * @param class-string<Doc>|null $type
	 *
	 * @return ($type is class-string<Doc> ? array<Doc> : array<SupportedClasses>)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Configuration\FindConnectorProperties $queryObject,
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
				if ($type === MetadataDocuments\DevicesModule\ConnectorDynamicProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_DYNAMIC . '")]');

				} elseif ($type === MetadataDocuments\DevicesModule\ConnectorVariableProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_VARIABLE . '")]');
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
				function (stdClass $item) use ($type): MetadataDocuments\DevicesModule\ConnectorDynamicProperty|MetadataDocuments\DevicesModule\ConnectorVariableProperty|null {
					if (is_string($type)) {
						return $this->entityFactory->create($type, $item);
					} else {
						foreach (
							[
								MetadataDocuments\DevicesModule\ConnectorDynamicProperty::class,
								MetadataDocuments\DevicesModule\ConnectorVariableProperty::class,
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
