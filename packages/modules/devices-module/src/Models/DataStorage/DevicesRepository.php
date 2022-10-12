<?php declare(strict_types = 1);

/**
 * DevicesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DataStorage!
 * @subpackage     Models
 * @since          0.62.0
 *
 * @date           16.06.22
 */

namespace FastyBird\DevicesModule\Models\DataStorage;

use Countable;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use IteratorAggregate;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use RecursiveArrayIterator;
use function array_key_exists;
use function count;
use function strval;

/**
 * Data storage devices repository
 *
 * @implements IteratorAggregate<int, MetadataEntities\DevicesModule\Device>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicesRepository implements Countable, IteratorAggregate
{

	use Nette\SmartObject;

	/** @var Array<string, Array<string, mixed>> */
	private array $rawData;

	/** @var Array<string, MetadataEntities\DevicesModule\Device> */
	private array $entities;

	public function __construct(
		private readonly MetadataEntities\DevicesModule\DeviceEntityFactory $entityFactory,
	)
	{
		$this->rawData = [];
		$this->entities = [];
	}

	/**
	 * @throws MetadataExceptions\FileNotFound
	 */
	public function findById(Uuid\UuidInterface $id): MetadataEntities\DevicesModule\Device|null
	{
		if (array_key_exists($id->toString(), $this->rawData)) {
			return $this->getEntity($id, $this->rawData[$id->toString()]);
		}

		return null;
	}

	/**
	 * @throws MetadataExceptions\FileNotFound
	 */
	public function findByIdentifier(
		Uuid\UuidInterface $connector,
		string $identifier,
	): MetadataEntities\DevicesModule\Device|null
	{
		foreach ($this->rawData as $id => $rawDataRow) {
			if (
				array_key_exists('connector', $rawDataRow)
				&& $connector->toString() === $rawDataRow['connector']
				&& array_key_exists('identifier', $rawDataRow)
				&& Utils\Strings::lower(strval($rawDataRow['identifier'])) === Utils\Strings::lower($identifier)
			) {
				return $this->getEntity(Uuid\Uuid::fromString($id), $rawDataRow);
			}
		}

		return null;
	}

	/**
	 * @return Array<int, MetadataEntities\DevicesModule\Device>
	 *
	 * @throws MetadataExceptions\FileNotFound
	 */
	public function findAllByConnector(Uuid\UuidInterface $connector): array
	{
		$entities = [];

		foreach ($this->rawData as $id => $rawDataRow) {
			if (array_key_exists('connector', $rawDataRow) && $connector->toString() === $rawDataRow['connector']) {
				$entities[] = $this->getEntity(Uuid\Uuid::fromString($id), $rawDataRow);
			}
		}

		return $entities;
	}

	/**
	 * @param Array<string, mixed> $data
	 */
	public function append(Uuid\UuidInterface $id, array $data): void
	{
		$this->rawData[$id->toString()] = $data;

		if (array_key_exists($id->toString(), $this->entities)) {
			unset($this->entities[$id->toString()]);
		}
	}

	public function clear(): void
	{
		$this->rawData = [];
		$this->entities = [];
	}

	/**
	 * @param Uuid\UuidInterface|Array<Uuid\UuidInterface> $id
	 */
	public function reset(Uuid\UuidInterface|array $id): void
	{
		if ($id instanceof Uuid\UuidInterface) {
			if (array_key_exists($id->toString(), $this->entities)) {
				unset($this->entities[$id->toString()]);
			}
		} else {
			$ids = $id;

			foreach ($ids as $id) {
				if (array_key_exists($id->toString(), $this->entities)) {
					unset($this->entities[$id->toString()]);
				}
			}
		}
	}

	public function count(): int
	{
		return count($this->rawData);
	}

	/**
	 * @return RecursiveArrayIterator<int, MetadataEntities\DevicesModule\Device>
	 *
	 * @throws MetadataExceptions\FileNotFound
	 */
	public function getIterator(): RecursiveArrayIterator
	{
		$entities = [];

		foreach ($this->rawData as $id => $rawDataRow) {
			$entities[] = $this->getEntity(Uuid\Uuid::fromString($id), $rawDataRow);
		}

		/** @var RecursiveArrayIterator<int, MetadataEntities\DevicesModule\Device> $result */
		$result = new RecursiveArrayIterator($entities);

		return $result;
	}

	/**
	 * @param Array<string, mixed> $data
	 *
	 * @throws MetadataExceptions\FileNotFound
	 */
	private function getEntity(
		Uuid\UuidInterface $id,
		array $data,
	): MetadataEntities\DevicesModule\Device
	{
		if (!array_key_exists($id->toString(), $this->entities)) {
			$this->entities[$id->toString()] = $this->entityFactory->create($data);
		}

		return $this->entities[$id->toString()];
	}

}
