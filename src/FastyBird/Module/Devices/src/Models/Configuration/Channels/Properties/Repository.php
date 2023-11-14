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

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Entities\DevicesModule\Channel as T;
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
use function in_array;
use function is_array;
use function is_string;

/**
 * Channels properties configuration repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Repository
{

	public function __construct(
		private readonly Models\Configuration\Builder $builder,
		private readonly MetadataEntities\EntityFactory $entityFactory,
	)
	{
	}

	/**
	 * @template T of MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelVariableProperty|MetadataEntities\DevicesModule\ChannelMappedProperty
	 *
	 * @param Queries\Configuration\FindChannelProperties<T> $queryObject
	 * @param class-string<T>|array<class-string<T>> $type
	 *
	 * @return T|null
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function findOneBy(
		Queries\Configuration\FindChannelProperties $queryObject,
		string|array $type = [
			MetadataEntities\DevicesModule\ChannelDynamicProperty::class,
			MetadataEntities\DevicesModule\ChannelVariableProperty::class,
			MetadataEntities\DevicesModule\ChannelMappedProperty::class,
		],
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	): MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelVariableProperty|MetadataEntities\DevicesModule\ChannelMappedProperty|null
	{
		try {
			$space = $this->builder
				->load()
				->find('.properties.*');

			if (is_string($type)) {
				if ($type === MetadataEntities\DevicesModule\ChannelDynamicProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_DYNAMIC . '")]');

				} elseif ($type === MetadataEntities\DevicesModule\ChannelVariableProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_VARIABLE . '")]');

				} elseif ($type === MetadataEntities\DevicesModule\ChannelMappedProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_MAPPED . '")]');
				}
			} else {
				$types = [];

				foreach (
					[
						MetadataEntities\DevicesModule\ChannelDynamicProperty::class,
						MetadataEntities\DevicesModule\ChannelVariableProperty::class,
						MetadataEntities\DevicesModule\ChannelMappedProperty::class,
					] as $class
				) {
					if (in_array($class, $type, true)) {
						if ($class === MetadataEntities\DevicesModule\ChannelDynamicProperty::class) {
							$types[] = MetadataTypes\PropertyType::TYPE_DYNAMIC;

						} elseif ($class === MetadataEntities\DevicesModule\ChannelVariableProperty::class) {
							$types[] = MetadataTypes\PropertyType::TYPE_VARIABLE;

						} elseif ($class === MetadataEntities\DevicesModule\ChannelMappedProperty::class) {
							$types[] = MetadataTypes\PropertyType::TYPE_MAPPED;
						}
					}
				}

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
			foreach ($type as $class) {
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
	 * @template T of MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelVariableProperty|MetadataEntities\DevicesModule\ChannelMappedProperty
	 *
	 * @param Queries\Configuration\FindChannelProperties<T> $queryObject
	 * @param class-string<T>|array<class-string<T>> $type
	 *
	 * @return array<T>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Configuration\FindChannelProperties $queryObject,
		string|array $type = [
			MetadataEntities\DevicesModule\ChannelDynamicProperty::class,
			MetadataEntities\DevicesModule\ChannelVariableProperty::class,
			MetadataEntities\DevicesModule\ChannelMappedProperty::class,
		],
	): array
	{
		try {
			$space = $this->builder
				->load()
				->find('.properties.*');

			if (is_string($type)) {
				if ($type === MetadataEntities\DevicesModule\ChannelDynamicProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_DYNAMIC . '")]');

				} elseif ($type === MetadataEntities\DevicesModule\ChannelVariableProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_VARIABLE . '")]');

				} elseif ($type === MetadataEntities\DevicesModule\ChannelMappedProperty::class) {
					$space = $space->find('.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_MAPPED . '")]');
				}
			} else {
				$types = [];

				foreach (
					[
						MetadataEntities\DevicesModule\ChannelDynamicProperty::class,
						MetadataEntities\DevicesModule\ChannelVariableProperty::class,
						MetadataEntities\DevicesModule\ChannelMappedProperty::class,
					] as $class
				) {
					if (in_array($class, $type, true)) {
						if ($class === MetadataEntities\DevicesModule\ChannelDynamicProperty::class) {
							$types[] = MetadataTypes\PropertyType::TYPE_DYNAMIC;

						} elseif ($class === MetadataEntities\DevicesModule\ChannelVariableProperty::class) {
							$types[] = MetadataTypes\PropertyType::TYPE_VARIABLE;

						} elseif ($class === MetadataEntities\DevicesModule\ChannelMappedProperty::class) {
							$types[] = MetadataTypes\PropertyType::TYPE_MAPPED;
						}
					}
				}

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
				function (stdClass $item) use ($type): MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelVariableProperty|MetadataEntities\DevicesModule\ChannelMappedProperty|null {
					if (is_string($type)) {
						return $this->entityFactory->create($type, $item);
					} else {
						foreach ($type as $class) {
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
