<?php declare(strict_types = 1);

/**
 * DeviceMappedProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Entities\DevicesModule;

use FastyBird\Library\Metadata\Exceptions;
use Ramsey\Uuid;
use function array_merge;

/**
 * Device mapped property entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceMappedProperty extends MappedProperty
{

	private Uuid\UuidInterface $device;

	private Uuid\UuidInterface|null $parent;

	/**
	 * @param array<int, string>|array<int, string|int|float|array<int, string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 */
	public function __construct(
		string $id,
		string $device,
		string $type,
		string $category,
		string $identifier,
		string|null $name,
		bool $settable,
		bool $queryable,
		string $dataType,
		string|null $unit = null,
		array|null $format = null,
		string|int|float|null $invalid = null,
		int|null $scale = null,
		float|null $step = null,
		float|bool|int|string|null $actualValue = null,
		float|bool|int|string|null $previousValue = null,
		float|bool|int|string|null $expectedValue = null,
		bool|string $pending = false,
		bool $valid = false,
		float|bool|int|string|null $value = null,
		float|bool|int|string|null $default = null,
		string|null $parent = null,
		string|null $owner = null,
	)
	{
		parent::__construct(
			$id,
			$type,
			$category,
			$identifier,
			$name,
			$settable,
			$queryable,
			$dataType,
			$unit,
			$format,
			$invalid,
			$scale,
			$step,
			$actualValue,
			$previousValue,
			$expectedValue,
			$pending,
			$valid,
			$value,
			$default,
			$owner,
		);

		$this->device = Uuid\Uuid::fromString($device);
		$this->parent = $parent !== null ? Uuid\Uuid::fromString($parent) : null;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getParent(): Uuid\UuidInterface|null
	{
		return $this->parent;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'device' => $this->getDevice()->toString(),
			'parent' => $this->getParent()?->toString(),
		]);
	}

	/**
	 * @return array<string, mixed>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
