<?php declare(strict_types = 1);

/**
 * Dynamic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           04.01.22
 */

namespace FastyBird\Module\Devices\Entities\Channels\Properties;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions;
use function array_map;
use function array_merge;
use function sprintf;

/**
 * @ORM\Entity
 */
class Dynamic extends Property
{

	public const TYPE = MetadataTypes\PropertyType::DYNAMIC;

	public static function getType(): string
	{
		return self::TYPE;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getParent(): Property|null
	{
		throw new Exceptions\InvalidState(
			sprintf('Parent could not be read for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setParent(Property $property): void
	{
		throw new Exceptions\InvalidState(
			sprintf('Parent could not be assigned for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function removeParent(): void
	{
		throw new Exceptions\InvalidState(
			sprintf('Parent could not be unassigned for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		throw new Exceptions\InvalidState(
			sprintf('Reading value is not allowed for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setValue(
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): void
	{
		throw new Exceptions\InvalidState(
			sprintf(
				'Writing value is not allowed for property type: %s',
				static::getType(),
			),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getDefault(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		throw new Exceptions\InvalidState(
			sprintf(
				'Reading default  value is not allowed for property type: %s',
				static::getType(),
			),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setDefault(
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $default,
	): void
	{
		throw new Exceptions\InvalidState(
			sprintf(
				'Writing default value is not allowed for property type: %s',
				static::getType(),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'children' => array_map(
				static fn (Property $child): string => $child->getId()->toString(),
				$this->getChildren(),
			),
		]);
	}

}
