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
 * Devices configuration repository
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
		private readonly MetadataDocuments\DocumentFactory $entityFactory,
	)
	{
	}

	/**
	 * @template T of MetadataDocuments\DevicesModule\Device
	 *
	 * @param Queries\Configuration\FindDevices<T> $queryObject
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
		Queries\Configuration\FindDevices $queryObject,
		string $type = MetadataDocuments\DevicesModule\Device::class,
	): MetadataDocuments\DevicesModule\Device|null
	{
		try {
			$space = $this->builder
				->load()
				->find('.' . Devices\Constants::DATA_STORAGE_DEVICES_KEY . '.*');
		} catch (JSONPath\JSONPathException $ex) {
			throw new Exceptions\InvalidState('', $ex->getCode(), $ex);
		}

		$result = $queryObject->fetch($space);

		if (!is_array($result) || $result === []) {
			return null;
		}

		return $this->entityFactory->create($type, $result[0]);
	}

	/**
	 * @template T of MetadataDocuments\DevicesModule\Device
	 *
	 * @param Queries\Configuration\FindDevices<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return array<T>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Configuration\FindDevices $queryObject,
		string $type = MetadataDocuments\DevicesModule\Device::class,
	): array
	{
		try {
			$space = $this->builder
				->load()
				->find('.' . Devices\Constants::DATA_STORAGE_DEVICES_KEY . '.*');
		} catch (JSONPath\JSONPathException $ex) {
			throw new Exceptions\InvalidState('Fetch all data by query failed', $ex->getCode(), $ex);
		}

		$result = $queryObject->fetch($space);

		if (!is_array($result)) {
			return [];
		}

		return array_map(
			fn (stdClass $item): MetadataDocuments\DevicesModule\Device => $this->entityFactory->create($type, $item),
			$result,
		);
	}

}
