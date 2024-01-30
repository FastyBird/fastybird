<?php declare(strict_types = 1);

/**
 * AccessoryFactory.php
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
 * HAP accessory factory interface
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface AccessoryFactory
{

	public function create(
		string $name,
		int|null $aid,
		Types\AccessoryCategory $category,
		MetadataDocuments\DevicesModule\Device $device,
	): Accessory;

	/**
	 * @return class-string<Entities\HomeKitDevice>
	 */
	public function getEntityClass(): string;

}
