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

namespace FastyBird\Module\Devices\Models\Configuration\Channels\Controls;

use Contributte\Cache;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use Nette\Caching;
use stdClass;
use Throwable;
use function array_map;
use function is_array;

/**
 * Channels controls configuration repository
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
	 * @param Queries\Configuration\FindChannelControls<MetadataDocuments\DevicesModule\ChannelControl> $queryObject
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Configuration\FindChannelControls $queryObject,
	): MetadataDocuments\DevicesModule\ChannelControl|null
	{
		try {
			$document = $this->cache->load(
				$this->createKeyOne($queryObject),
				function () use ($queryObject, &$dependencies): MetadataDocuments\DevicesModule\ChannelControl|null {
					$dependencies[Caching\Cache::Files] = $this->builder->getConfigurationFile();

					$space = $this->builder
						->load()
						->find('.' . Devices\Constants::DATA_STORAGE_CONTROLS_KEY . '.*');

					$result = $queryObject->fetch($space);

					if (!is_array($result) || $result === []) {
						return null;
					}

					return $this->entityFactory->create(
						MetadataDocuments\DevicesModule\ChannelControl::class,
						$result[0],
					);
				},
			);
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Could not load document', $ex->getCode(), $ex);
		}

		if ($document !== null && !$document instanceof MetadataDocuments\DevicesModule\ChannelControl) {
			throw new Exceptions\InvalidState('Could not load document');
		}

		return $document;
	}

	/**
	 * @param Queries\Configuration\FindChannelControls<MetadataDocuments\DevicesModule\ChannelControl> $queryObject
	 *
	 * @return array<MetadataDocuments\DevicesModule\ChannelControl>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Configuration\FindChannelControls $queryObject,
	): array
	{
		try {
			$documents = $this->cache->load(
				$this->createKeyAll($queryObject),
				function () use ($queryObject, &$dependencies): array {
					$dependencies[Caching\Cache::Files] = $this->builder->getConfigurationFile();

					$space = $this->builder
						->load()
						->find('.' . Devices\Constants::DATA_STORAGE_CONTROLS_KEY . '.*');

					$result = $queryObject->fetch($space);

					if (!is_array($result)) {
						return [];
					}

					return array_map(
						fn (stdClass $item): MetadataDocuments\DevicesModule\ChannelControl => $this->entityFactory->create(
							MetadataDocuments\DevicesModule\ChannelControl::class,
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
