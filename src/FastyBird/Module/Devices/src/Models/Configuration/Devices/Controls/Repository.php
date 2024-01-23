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

use Contributte\Cache;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use Ramsey\Uuid;
use stdClass;
use Throwable;
use function array_map;
use function is_array;

/**
 * Devices controls configuration repository
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
	 * @throws Exceptions\InvalidState
	 */
	public function find(
		Uuid\UuidInterface $id,
	): MetadataDocuments\DevicesModule\DeviceControl|null
	{
		$queryObject = new Queries\Configuration\FindDeviceControls();
		$queryObject->byId($id);

		return $this->findOneBy($queryObject);
	}

	/**
	 * @param Queries\Configuration\FindDeviceControls<MetadataDocuments\DevicesModule\DeviceControl> $queryObject
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Configuration\FindDeviceControls $queryObject,
	): MetadataDocuments\DevicesModule\DeviceControl|null
	{
		try {
			$document = $this->cache->load(
				$this->createKeyOne($queryObject),
				function () use ($queryObject): MetadataDocuments\DevicesModule\DeviceControl|false {
					$space = $this->builder
						->load()
						->find('.' . Devices\Constants::DATA_STORAGE_CONTROLS_KEY . '.*');

					$result = $queryObject->fetch($space);

					if (!is_array($result) || $result === []) {
						return false;
					}

					return $this->documentFactory->create(
						MetadataDocuments\DevicesModule\DeviceControl::class,
						$result[0],
					);
				},
			);
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Could not load document', $ex->getCode(), $ex);
		}

		if ($document === false) {
			return null;
		}

		if (!$document instanceof MetadataDocuments\DevicesModule\DeviceControl) {
			throw new Exceptions\InvalidState('Could not load document');
		}

		return $document;
	}

	/**
	 * @param Queries\Configuration\FindDeviceControls<MetadataDocuments\DevicesModule\DeviceControl> $queryObject
	 *
	 * @return array<MetadataDocuments\DevicesModule\DeviceControl>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Configuration\FindDeviceControls $queryObject,
	): array
	{
		try {
			$documents = $this->cache->load(
				$this->createKeyAll($queryObject),
				function () use ($queryObject): array {
					$space = $this->builder
						->load()
						->find('.' . Devices\Constants::DATA_STORAGE_CONTROLS_KEY . '.*');

					$result = $queryObject->fetch($space);

					if (!is_array($result)) {
						return [];
					}

					return array_map(
						fn (stdClass $item): MetadataDocuments\DevicesModule\DeviceControl => $this->documentFactory->create(
							MetadataDocuments\DevicesModule\DeviceControl::class,
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
