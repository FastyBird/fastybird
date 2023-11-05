<?php declare(strict_types = 1);

/**
 * Variable.php
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

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions;

/**
 * @ORM\Entity
 */
class Variable extends Property
{

	public function getType(): MetadataTypes\PropertyType
	{
		return MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::TYPE_VARIABLE);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setSettable(bool $settable): void
	{
		throw new Exceptions\InvalidState('Settable flag is allowed only for dynamic properties');
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function isSettable(): bool
	{
		throw new Exceptions\InvalidState('Settable flag is allowed only for dynamic properties');
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setQueryable(bool $queryable): void
	{
		throw new Exceptions\InvalidState('Queryable flag is allowed only for dynamic properties');
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function isQueryable(): bool
	{
		throw new Exceptions\InvalidState('Queryable flag is allowed only for dynamic properties');
	}

}
