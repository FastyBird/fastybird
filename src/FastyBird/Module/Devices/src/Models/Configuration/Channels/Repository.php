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

namespace FastyBird\Module\Devices\Models\Configuration\Channels;

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
use function serialize;

/**
 * Channels configuration repository
 *
 * @template T of MetadataDocuments\DevicesModule\Channel
 * @extends  Models\Configuration\Repository<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Repository extends Models\Configuration\Repository
{

	public function __construct(
		Models\Configuration\Builder $builder,
		private readonly MetadataDocuments\DocumentFactory $entityFactory,
	)
	{
		parent::__construct($builder);
	}

	/**
	 * @param Queries\Configuration\FindChannels<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function findOneBy(
		Queries\Configuration\FindChannels $queryObject,
		string $type = MetadataDocuments\DevicesModule\Channel::class,
	): MetadataDocuments\DevicesModule\Channel|null
	{
		$document = $this->loadCacheOne(serialize($queryObject->toString() . $type));

		if ($document !== false) {
			return $document;
		}

		try {
			$space = $this->builder
				->load()
				->find('.' . Devices\Constants::DATA_STORAGE_CHANNELS_KEY . '.*');
		} catch (JSONPath\JSONPathException $ex) {
			throw new Exceptions\InvalidState('', $ex->getCode(), $ex);
		}

		$result = $queryObject->fetch($space);

		if (!is_array($result) || $result === []) {
			return null;
		}

		$document = $this->entityFactory->create($type, $result[0]);

		$this->writeCacheOne(serialize($queryObject->toString() . $type), $document);

		return $document;
	}

	/**
	 * @param Queries\Configuration\FindChannels<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return array<T>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Configuration\FindChannels $queryObject,
		string $type = MetadataDocuments\DevicesModule\Channel::class,
	): array
	{
		$documents = $this->loadCacheAll(serialize($queryObject->toString() . $type));

		if ($documents !== false) {
			return $documents;
		}

		try {
			$space = $this->builder
				->load()
				->find('.' . Devices\Constants::DATA_STORAGE_CHANNELS_KEY . '.*');
		} catch (JSONPath\JSONPathException $ex) {
			throw new Exceptions\InvalidState('Fetch all data by query failed', $ex->getCode(), $ex);
		}

		$result = $queryObject->fetch($space);

		if (!is_array($result)) {
			return [];
		}

		$documents = array_map(
			fn (stdClass $item): MetadataDocuments\DevicesModule\Channel => $this->entityFactory->create($type, $item),
			$result,
		);

		$this->writeCacheAll(serialize($queryObject->toString() . $type), $documents);

		return $documents;
	}

}
