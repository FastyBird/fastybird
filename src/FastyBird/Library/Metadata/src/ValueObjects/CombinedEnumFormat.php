<?php declare(strict_types = 1);

/**
 * CombinedEnumFormat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     ValueObjects
 * @since          1.0.0
 *
 * @date           05.08.22
 */

namespace FastyBird\Library\Metadata\ValueObjects;

use ArrayIterator;
use FastyBird\Library\Metadata\Exceptions;
use IteratorAggregate;
use Nette;
use Traversable;
use function array_map;
use function explode;
use function implode;
use function is_string;
use function strval;
use function trim;

/**
 * Combined enum value format
 *
 * @implements     IteratorAggregate<int, array<int, CombinedEnumFormatItem|null>>
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     ValueObjects
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CombinedEnumFormat implements IteratorAggregate
{

	use Nette\SmartObject;

	/** @var array<int, array<int, CombinedEnumFormatItem|null>> */
	private array $items;

	/**
	 * @param string|array<int, array<int, string|array<int, string|int|float|bool|null>|null>> $items
	 */
	public function __construct(string|array $items)
	{
		if (is_string($items)) {
			$this->items = array_map(static function (string $item): array {
				if (trim($item) === '') {
					throw new Exceptions\InvalidArgument('Provided format is not valid for combined enum format');
				}

				$parts = explode(':', $item) + [null, null, null];

				return array_map(static function (string|null $part): CombinedEnumFormatItem|null {
					if ($part === null || trim($part) === '') {
						return null;
					}

					return new CombinedEnumFormatItem($part);
				}, $parts);
			}, explode(',', $items));
		} else {
			$this->items = array_map(static function (array $item): array {
				if ($item === []) {
					throw new Exceptions\InvalidArgument('Provided format is not valid for combined enum format');
				}

				return array_map(static function (string|array|null $part): CombinedEnumFormatItem|null {
					if ($part === null || $part === []) {
						return null;
					}

					return new CombinedEnumFormatItem($part);
				}, $item);
			}, $items);
		}
	}

	/**
	 * @return array<int, array<int, CombinedEnumFormatItem|null>>
	 */
	public function getItems(): array
	{
		return $this->items;
	}

	/**
	 * @return array<int, array<int, string|array<int, string|int|float|bool>|null>>
	 */
	public function toArray(): array
	{
		return array_map(
			static fn (array $item): array => array_map(
				static function (CombinedEnumFormatItem|null $part): array|string|null {
						if ($part instanceof CombinedEnumFormatItem) {
							return $part->getDataType() !== null ? $part->toArray() : strval($part->getValue());
						}

					return $part;
				},
				$item,
			),
			$this->getItems(),
		);
	}

	public function getIterator(): Traversable
	{
		return new ArrayIterator($this->getItems());
	}

	public function __toString(): string
	{
		return implode(',', array_map(static fn (array $item) =>
			implode(':', array_map(static function (CombinedEnumFormatItem|null $part): string {
				if ($part instanceof CombinedEnumFormatItem) {
					return strval($part);
				}

				return '';
			}, $item)), $this->getItems()));
	}

}
