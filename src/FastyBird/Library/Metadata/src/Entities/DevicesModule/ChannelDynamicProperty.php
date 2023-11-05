<?php declare(strict_types = 1);

/**
 * ChannelDynamicProperty.php
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
use Nette\Utils;
use Ramsey\Uuid;
use function array_map;
use function array_merge;

/**
 * Channel dynamic property entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelDynamicProperty extends DynamicProperty
{

	private Uuid\UuidInterface $channel;

	/** @var array<Uuid\UuidInterface> */
	private array $children;

	/**
	 * @param array<int, string>|array<int, string|int|float|array<int, string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 * @param array<int, string>|Utils\ArrayHash<string> $children
	 */
	public function __construct(
		string $id,
		string $channel,
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
		bool|string|null $pending = null,
		bool|null $valid = null,
		array|Utils\ArrayHash $children = [],
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
			$owner,
		);

		$this->channel = Uuid\Uuid::fromString($channel);
		$this->children = array_map(
			static fn (string $item): Uuid\UuidInterface => Uuid\Uuid::fromString($item),
			(array) $children,
		);
	}

	public function getChannel(): Uuid\UuidInterface
	{
		return $this->channel;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getChildren(): array
	{
		return $this->children;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'channel' => $this->getChannel()->toString(),
			'children' => array_map(
				static fn (Uuid\UuidInterface $child): string => $child->toString(),
				$this->getChildren(),
			),
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
