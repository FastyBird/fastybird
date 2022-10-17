<?php declare(strict_types = 1);

/**
 * ChannelDynamicProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Metadata\Entities\DevicesModule;

use Exception;
use Nette\Utils;
use Ramsey\Uuid;
use function array_map;
use function array_merge;

/**
 * Channel dynamic property entity
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelDynamicProperty extends DynamicProperty
{

	use TChannelProperty;

	/**
	 * @param Array<int, string>|Array<int, string|int|float|Array<int, string|int|float>|null>|Array<int, Array<int, string|Array<int, string|int|float|bool>|null>>|null $format
	 * @param Array<int, string>|Utils\ArrayHash<string> $children
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
		float|bool|int|string|null $actualValue = null,
		float|bool|int|string|null $previousValue = null,
		float|bool|int|string|null $expectedValue = null,
		bool|string|null $pending = null,
		bool|null $valid = null,
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
			$actualValue,
			$previousValue,
			$expectedValue,
			$pending,
			$valid,
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
	 * @return Array<string, mixed>
	 *
	 * @throws Exception
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
