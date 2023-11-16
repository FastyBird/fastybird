<?php declare(strict_types = 1);

/**
 * DeviceVariableProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Documents\DevicesModule;

use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;
use function array_merge;

/**
 * Device variable property document
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceVariableProperty extends VariableProperty
{

	/**
	 * @param string|array<int, string>|array<int, int>|array<int, float>|array<int, bool|string|int|float|array<int, bool|string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 * @param array<int, Uuid\UuidInterface> $children
	 */
	public function __construct(
		Uuid\UuidInterface $id,
		#[BootstrapObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $device,
		Types\PropertyType $type,
		Types\PropertyCategory $category,
		string $identifier,
		string|null $name,
		Types\DataType $dataType,
		string|null $unit = null,
		string|array|null $format = null,
		float|int|string|null $invalid = null,
		int|null $scale = null,
		int|float|null $step = null,
		bool|float|int|string|null $value = null,
		bool|float|int|string|null $default = null,
		#[ObjectMapper\Rules\ArrayOf(
			new BootstrapObjectMapper\Rules\UuidValue(),
		)]
		private readonly array $children = [],
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct(
			$id,
			$type,
			$category,
			$identifier,
			$name,
			$dataType,
			$unit,
			$format,
			$invalid,
			$scale,
			$step,
			$value,
			$default,
			$owner,
		);
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getChildren(): array
	{
		return $this->children;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'device' => $this->getDevice()->toString(),
			'children' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getChildren(),
			),
		]);
	}

	/**
	 * @return array<string, mixed>
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}