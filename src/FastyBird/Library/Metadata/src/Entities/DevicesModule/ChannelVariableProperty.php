<?php declare(strict_types = 1);

/**
 * ChannelVariableProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Entities\DevicesModule;

use Nette\Utils;
use Ramsey\Uuid;
use function array_map;
use function array_merge;

/**
 * Channel variable property entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelVariableProperty extends VariableProperty
{

	use TChannelProperty;

	/**
	 * @param array<int, string>|array<int, string|int|float|array<int, string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 * @param array<int, string>|Utils\ArrayHash<string> $children
	 */
	public function __construct(
		string $id,
		string $channel,
		string $type,
		string $identifier,
		string|null $name,
		bool $settable,
		bool $queryable,
		string $dataType,
		string|null $unit = null,
		array|null $format = null,
		string|int|float|null $invalid = null,
		int|null $numberOfDecimals = null,
		float|bool|int|string|null $value = null,
		float|bool|int|string|null $default = null,
		string|null $parent = null,
		array|Utils\ArrayHash $children = [],
		string|null $owner = null,
	)
	{
		parent::__construct(
			$id,
			$type,
			$identifier,
			$name,
			$settable,
			$queryable,
			$dataType,
			$unit,
			$format,
			$invalid,
			$numberOfDecimals,
			$value,
			$default,
			$owner,
		);

		$this->channel = Uuid\Uuid::fromString($channel);
		$this->parent = $parent !== null ? Uuid\Uuid::fromString($parent) : null;
		$this->children = array_map(
			static fn (string $item): Uuid\UuidInterface => Uuid\Uuid::fromString($item),
			(array) $children,
		);
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'channel' => $this->getChannel()->toString(),
			'parent' => $this->getParent()?->toString(),
			'children' => array_map(
				static fn (Uuid\UuidInterface $child): string => $child->toString(),
				$this->getChildren(),
			),
		]);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
