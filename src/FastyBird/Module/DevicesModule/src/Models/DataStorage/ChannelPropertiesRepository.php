<?php declare(strict_types = 1);

/**
 * ChannelPropertiesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DataStorage!
 * @subpackage     Models
 * @since          0.62.0
 *
 * @date           17.06.22
 */

namespace FastyBird\DevicesModule\Models\DataStorage;

use Countable;
use FastyBird\DevicesModule\Exceptions;
use FastyBird\DevicesModule\Models;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Metadata\Types as MetadataTypes;
use IteratorAggregate;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use RecursiveArrayIterator;
use function array_key_exists;
use function array_merge;
use function count;
use function is_a;
use function is_subclass_of;
use function strval;

/**
 * Data storage channel properties repository
 *
 * @implements IteratorAggregate<int, MetadataEntities\DevicesModule\ChannelVariableProperty|MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelMappedProperty>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelPropertiesRepository implements Countable, IteratorAggregate
{

	use Nette\SmartObject;

	/** @var Array<string, Array<string, mixed>> */
	private array $rawData;

	/** @var Array<string, MetadataEntities\DevicesModule\ChannelVariableProperty|MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelMappedProperty> */
	private array $entities;

	public function __construct(
		private readonly Models\States\ChannelPropertiesRepository $statesRepository,
		private readonly MetadataEntities\DevicesModule\ChannelPropertyEntityFactory $entityFactory,
	)
	{
		$this->rawData = [];
		$this->entities = [];
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function findById(
		Uuid\UuidInterface $id,
	): MetadataEntities\DevicesModule\ChannelVariableProperty|MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelMappedProperty|null
	{
		if (array_key_exists($id->toString(), $this->rawData)) {
			return $this->getEntity($id, $this->rawData[$id->toString()]);
		}

		return null;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function findByIdentifier(
		Uuid\UuidInterface $channel,
		string $identifier,
	): MetadataEntities\DevicesModule\ChannelVariableProperty|MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelMappedProperty|null
	{
		foreach ($this->rawData as $id => $rawDataRow) {
			if (
				array_key_exists('channel', $rawDataRow)
				&& $channel->toString() === $rawDataRow['channel']
				&& array_key_exists('identifier', $rawDataRow)
				&& Utils\Strings::lower(strval($rawDataRow['identifier'])) === Utils\Strings::lower($identifier)
			) {
				return $this->getEntity(Uuid\Uuid::fromString($id), $rawDataRow);
			}
		}

		return null;
	}

	/**
	 * @param class-string<T>|null $type
	 *
	 * @return Array<int, MetadataEntities\DevicesModule\ChannelVariableProperty|MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelMappedProperty>
	 *
	 * @template T
	 *
	 * @phpstan-return ($type is null ? Array<int, MetadataEntities\DevicesModule\ChannelVariableProperty|MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelMappedProperty> : Array<int, T>)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function findAllByChannel(
		Uuid\UuidInterface $channel,
		string|null $type = null,
	): array
	{
		$entities = [];

		foreach ($this->rawData as $id => $rawDataRow) {
			if (array_key_exists('channel', $rawDataRow) && $channel->toString() === $rawDataRow['channel']) {
				$entity = $this->getEntity(Uuid\Uuid::fromString($id), $rawDataRow);

				if ($type === null || is_a($entity, $type) || is_subclass_of($entity, $type)) {
					$entities[] = $entity;
				}
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

			foreach ($ids as $subId) {
				if (array_key_exists($subId->toString(), $this->entities)) {
					unset($this->entities[$subId->toString()]);
				}
			}
		}
	}

	public function count(): int
	{
		return count($this->rawData);
	}

	/**
	 * @return RecursiveArrayIterator<int, MetadataEntities\DevicesModule\ChannelVariableProperty|MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelMappedProperty>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function getIterator(): RecursiveArrayIterator
	{
		$entities = [];

		foreach ($this->rawData as $id => $rawDataRow) {
			$entities[] = $this->getEntity(Uuid\Uuid::fromString($id), $rawDataRow);
		}

		/** @var RecursiveArrayIterator<int, MetadataEntities\DevicesModule\ChannelVariableProperty|MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelMappedProperty> $result */
		$result = new RecursiveArrayIterator($entities);

		return $result;
	}

	/**
	 * @param Array<string, mixed> $data
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function getEntity(
		Uuid\UuidInterface $id,
		array $data,
	): MetadataEntities\DevicesModule\ChannelVariableProperty|MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelMappedProperty
	{
		if (!array_key_exists($id->toString(), $this->entities)) {
			$state = [];

			if (
				array_key_exists('type', $data)
				&& (
					$data['type'] === MetadataTypes\PropertyType::TYPE_DYNAMIC
					|| $data['type'] === MetadataTypes\PropertyType::TYPE_MAPPED
				)
			) {
				$state = $this->loadPropertyState($id);
			}

			$entity = $this->entityFactory->create(array_merge($state, $data));

			if (
				$entity instanceof MetadataEntities\DevicesModule\ChannelVariableProperty
				|| $entity instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty
				|| $entity instanceof MetadataEntities\DevicesModule\ChannelMappedProperty
			) {
				$this->entities[$id->toString()] = $entity;
			} else {
				throw new Exceptions\InvalidState('Channel property entity could not be created');
			}
		}

		return $this->entities[$id->toString()];
	}

	/**
	 * @return Array<string, mixed>
	 */
	private function loadPropertyState(Uuid\UuidInterface $id): array
	{
		try {
			$entityState = $this->statesRepository->findOneById($id);

			return $entityState?->toArray() ?? [];
		} catch (Exceptions\NotImplemented) {
			return [];
		}
	}

}
