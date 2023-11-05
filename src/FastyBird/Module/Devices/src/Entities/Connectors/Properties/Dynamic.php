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
 * @date           08.02.22
 */

namespace FastyBird\Module\Devices\Entities\Connectors\Properties;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions;
use function sprintf;
use function strval;

/**
 * @ORM\Entity
 */
class Dynamic extends Property
{

	public function getType(): MetadataTypes\PropertyType
	{
		return MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::TYPE_DYNAMIC);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		throw new Exceptions\InvalidState(
			sprintf('Reading value is not allowed for property type: %s', strval($this->getType()->getValue())),
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
				strval($this->getType()->getValue()),
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
				strval($this->getType()->getValue()),
			),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setDefault(string|null $default): void
	{
		throw new Exceptions\InvalidState(
			sprintf(
				'Writing default value is not allowed for property type: %s',
				strval($this->getType()->getValue()),
			),
		);
	}

}
