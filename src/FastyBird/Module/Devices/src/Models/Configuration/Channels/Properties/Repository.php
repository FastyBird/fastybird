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

namespace FastyBird\Module\Devices\Models\Configuration\Channels\Properties;

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
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

/**
 * Channels properties configuration repository
 *
 * @phpstan-type SupportedClasses MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelVariableProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Repository
{

	public function __construct(
		private readonly Models\Configuration\Builder $builder,
		private readonly MetadataDocuments\DocumentFactory $entityFactory,
	)
	{
	}

	/**
	 * @template T of SupportedClasses
	 *
	 * @param Queries\Configuration\FindChannelProperties<T> $queryObject
	 * @param class-string<T>|null $type
	 *
	 * @return ($type is class-string<T> ? T|null : SupportedClasses|null)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function findOneBy(
		Queries\Configuration\FindChannelProperties $queryObject,
		string|null $type = null,
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	): MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelVariableProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|null
	{
		try {
			$space = $this->builder
				->load()
				->find('.properties.*');

			if (is_string($type)) {
				if ($type === MetadataDocuments\DevicesModule\ChannelDynamicProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_DYNAMIC . '")]');

				} elseif ($type === MetadataDocuments\DevicesModule\ChannelVariableProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_VARIABLE . '")]');

				} elseif ($type === MetadataDocuments\DevicesModule\ChannelMappedProperty::class) {
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
			return null;
		}

		if (is_string($type)) {
			return $this->entityFactory->create($type, $result[0]);
		} else {
			foreach (
				[
					MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
					MetadataDocuments\DevicesModule\ChannelVariableProperty::class,
					MetadataDocuments\DevicesModule\ChannelMappedProperty::class,
				] as $class
			) {
				try {
					return $this->entityFactory->create($class, $result[0]);
				} catch (Throwable) {
					// Just ignore it
				}
			}
		}

		return null;
	}

	/**
	 * @template T of SupportedClasses
	 *
	 * @param Queries\Configuration\FindChannelProperties<T> $queryObject
	 * @param class-string<T>|null $type
	 *
	 * @return ($type is class-string<T> ? array<T> : array<SupportedClasses>)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Configuration\FindChannelProperties $queryObject,
		string|null $type = null,
	): array
	{
		try {
			$space = $this->builder
				->load()
				->find('.properties.*');

			if (is_string($type)) {
				if ($type === MetadataDocuments\DevicesModule\ChannelDynamicProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_DYNAMIC . '")]');

				} elseif ($type === MetadataDocuments\DevicesModule\ChannelVariableProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_VARIABLE . '")]');

				} elseif ($type === MetadataDocuments\DevicesModule\ChannelMappedProperty::class) {
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

		return array_filter(
			array_map(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				function (stdClass $item) use ($type): MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelVariableProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|null {
					if (is_string($type)) {
						return $this->entityFactory->create($type, $item);
					} else {
						foreach (
							[
								MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
								MetadataDocuments\DevicesModule\ChannelVariableProperty::class,
								MetadataDocuments\DevicesModule\ChannelMappedProperty::class,
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
	}

}
