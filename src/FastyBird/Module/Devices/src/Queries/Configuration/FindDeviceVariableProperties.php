<?php declare(strict_types = 1);

/**
 * FindDeviceMappedProperties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           16.11.23
 */

namespace FastyBird\Module\Devices\Queries\Configuration;

use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Types;
use Ramsey\Uuid;

/**
 * Find device variable properties entities query
 *
 * @template T of Documents\Devices\Properties\Variable
 * @extends  FindDeviceProperties<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindDeviceVariableProperties extends FindDeviceProperties
{

	public function __construct()
	{
		parent::__construct();

		$this->filter[] = '.[?(@.type == "' . Types\PropertyType::VARIABLE->value . '")]';
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function forParent(Documents\Devices\Properties\Dynamic|Documents\Devices\Properties\Variable $parent): void
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

	public function byValue(string $value): void
	{
		$this->filter[] = '.[?(@.value =~ /(?i).*^' . $value . '*$/)]';
	}

}
