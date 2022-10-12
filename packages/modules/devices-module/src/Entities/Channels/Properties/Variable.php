<?php declare(strict_types = 1);

/**
 * Variable.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          0.9.0
 *
 * @date           04.01.22
 */

namespace FastyBird\DevicesModule\Entities\Channels\Properties;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\DevicesModule\Exceptions;
use FastyBird\Metadata\Types as MetadataTypes;

/**
 * @ORM\Entity
 */
class Variable extends Property
{

	public function getType(): MetadataTypes\PropertyType
	{
		return MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::TYPE_VARIABLE);
	}

	public function getValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|null
	{
		if ($this->getParent() !== null) {
			return $this->getParent()->getValue();
		}

		return parent::getValue();
	}

	public function setValue(string|null $value): void
	{
		if ($this->getParent() !== null) {
			throw new Exceptions\InvalidState('Value setter is allowed only for parent');
		}

		parent::setValue($value);
	}

}
