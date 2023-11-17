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
 * @date           15.11.23
 */

namespace FastyBird\Module\Devices\Models\Configuration\Devices\Controls;

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
 * Devices controls configuration repository
 *
 * @extends  Models\Configuration\Repository<MetadataDocuments\DevicesModule\DeviceControl>
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
	 * @param Queries\Configuration\FindDeviceControls<MetadataDocuments\DevicesModule\DeviceControl> $queryObject
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function findOneBy(
		Queries\Configuration\FindDeviceControls $queryObject,
	): MetadataDocuments\DevicesModule\DeviceControl|null
	{
		$document = $this->loadCacheOne($queryObject->toString());

		if ($document !== false) {
			return $document;
		}

		try {
			$space = $this->builder
				->load()
				->find('.' . Devices\Constants::DATA_STORAGE_CONTROLS_KEY . '.*');
		} catch (JSONPath\JSONPathException $ex) {
			throw new Exceptions\InvalidState('', $ex->getCode(), $ex);
		}

		$result = $queryObject->fetch($space);

		if (!is_array($result) || $result === []) {
			return null;
		}

		$document = $this->entityFactory->create(MetadataDocuments\DevicesModule\DeviceControl::class, $result[0]);

		$this->writeCacheOne($queryObject->toString(), $document);

		return $document;
	}

	/**
	 * @param Queries\Configuration\FindDeviceControls<MetadataDocuments\DevicesModule\DeviceControl> $queryObject
	 *
	 * @return array<MetadataDocuments\DevicesModule\DeviceControl>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Configuration\FindDeviceControls $queryObject,
	): array
	{
		$documents = $this->loadCacheAll($queryObject->toString());

		if ($documents !== false) {
			return $documents;
		}

		try {
			$space = $this->builder
				->load()
				->find('.' . Devices\Constants::DATA_STORAGE_CONTROLS_KEY . '.*');
		} catch (JSONPath\JSONPathException $ex) {
			throw new Exceptions\InvalidState('Fetch all data by query failed', $ex->getCode(), $ex);
		}

		$result = $queryObject->fetch($space);

		if (!is_array($result)) {
			return [];
		}

		$documents = array_map(
			fn (stdClass $item): MetadataDocuments\DevicesModule\DeviceControl => $this->entityFactory->create(
				MetadataDocuments\DevicesModule\DeviceControl::class,
				$item,
			),
			$result,
		);

		$this->writeCacheAll($queryObject->toString(), $documents);

		return $documents;
	}

}
