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
 * @date           13.11.23
 */

namespace FastyBird\Module\Devices\Models\Configuration\Connectors;

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use Flow\JSONPath;
use stdClass;
use function array_map;
use function is_array;

/**
 * Connectors configuration repository
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
	 * @template T of MetadataEntities\DevicesModule\Connector
	 *
	 * @param Queries\Configuration\FindConnectors<T> $queryObject
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
		Queries\Configuration\FindConnectors $queryObject,
		string $type = MetadataEntities\DevicesModule\Connector::class,
	): MetadataEntities\DevicesModule\Connector|null
	{
		try {
			$space = $this->builder
				->load()
				->find('.connectors.*');
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
	 * @template T of MetadataEntities\DevicesModule\Connector
	 *
	 * @param Queries\Configuration\FindConnectors<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return array<T>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Configuration\FindConnectors $queryObject,
		string $type = MetadataEntities\DevicesModule\Connector::class,
	): array
	{
		try {
			$space = $this->builder
				->load()
				->find('.connectors.*');
		} catch (JSONPath\JSONPathException $ex) {
			throw new Exceptions\InvalidState('Fetch all data by query failed', $ex->getCode(), $ex);
		}

		$result = $queryObject->fetch($space);

		if (!is_array($result)) {
			return [];
		}

		return array_map(
			fn (stdClass $item): MetadataEntities\DevicesModule\Connector => $this->entityFactory->create($type, $item),
			$result,
		);
	}

}
