<?php declare(strict_types = 1);

/**
 * ChannelMappedProperty.php
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

use DateTimeInterface;
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Types;
use Ramsey\Uuid;
use function array_merge;

/**
 * Channel mapped property document
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelMappedProperty extends MappedProperty
{

	/**
	 * @param string|array<int, string>|array<int, int>|array<int, float>|array<int, bool|string|int|float|array<int, bool|string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 */
	public function __construct(
		Uuid\UuidInterface $id,
		#[BootstrapObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $channel,
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
		bool|null $settable = null,
		bool|null $queryable = null,
		bool|float|int|string|null $actualValue = null,
		bool|float|int|string|null $previousValue = null,
		bool|float|int|string|null $expectedValue = null,
		bool|DateTimeInterface|null $pending = null,
		bool|null $valid = null,
		bool|float|int|string|null $value = null,
		bool|float|int|string|null $default = null,
		#[BootstrapObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $parent,
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
			$settable,
			$queryable,
			$actualValue,
			$previousValue,
			$expectedValue,
			$pending,
			$valid,
			$value,
			$default,
			$owner,
		);
	}

	public function getChannel(): Uuid\UuidInterface
	{
		return $this->channel;
	}

	public function getParent(): Uuid\UuidInterface
	{
		return $this->parent;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'channel' => $this->getChannel()->toString(),
			'parent' => $this->getParent()->toString(),
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
