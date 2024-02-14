<?php declare(strict_types = 1);

/**
 * FindChannelDynamicProperties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           14.11.23
 */

namespace FastyBird\Module\Devices\Queries\Configuration;

use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Types;
use Ramsey\Uuid;

/**
 * Find channel dynamic properties entities query
 *
 * @template T of Documents\Channels\Properties\Dynamic
 * @extends  FindChannelProperties<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindChannelDynamicProperties extends FindChannelProperties
{

	public function __construct()
	{
		parent::__construct();

		$this->filter[] = '.[?(@.type == "' . Types\PropertyType::DYNAMIC->value . '")]';
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function forParent(
		Documents\Channels\Properties\Dynamic|Documents\Channels\Properties\Variable $parent,
	): void
	{
		throw new Exceptions\InvalidState('Searching by parent is not allowed for this type of property');
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function byParentId(Uuid\UuidInterface $parentId): void
	{
		throw new Exceptions\InvalidState('Searching by parent is not allowed for this type of property');
	}

}
