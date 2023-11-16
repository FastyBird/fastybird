<?php declare(strict_types = 1);

/**
 * FindDeviceDynamicProperties.php
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

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions;
use Ramsey\Uuid;

/**
 * Find device dynamic properties entities query
 *
 * @template T of MetadataEntities\DevicesModule\DeviceDynamicProperty
 * @extends  FindDeviceProperties<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindDeviceDynamicProperties extends FindDeviceProperties
{

	public function __construct()
	{
		parent::__construct();

		$this->filter[] = '.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_DYNAMIC . '")]';
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function forParent(
		MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\DeviceVariableProperty $parent,
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
