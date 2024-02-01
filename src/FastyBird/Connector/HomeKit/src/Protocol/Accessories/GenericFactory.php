<?php declare(strict_types = 1);

/**
 * GenericFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           29.01.24
 */

namespace FastyBird\Connector\HomeKit\Protocol\Accessories;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;

/**
 * HAP generic accessory factory
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class GenericFactory implements AccessoryFactory
{

	public function create(
		string $name,
		int|null $aid,
		Types\AccessoryCategory $category,
		MetadataDocuments\DevicesModule\Device $device,
	): Generic
	{
		return new Generic($name, $aid, $category, $device);
	}

	public function getEntityClass(): string
	{
		return Entities\HomeKitDevice::class;
	}

}