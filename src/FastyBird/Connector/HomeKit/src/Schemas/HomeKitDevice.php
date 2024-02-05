<?php declare(strict_types = 1);

/**
 * HomeKitDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           29.03.22
 */

namespace FastyBird\Connector\HomeKit\Schemas;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * HomeKit connector entity schema
 *
 * @template T of Entities\HomeKitDevice
 * @extends  DevicesSchemas\Devices\Device<T>
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class HomeKitDevice extends DevicesSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Connector::HOMEKIT . '/device/' . Entities\HomeKitDevice::TYPE;

	public function getEntityClass(): string
	{
		return Entities\HomeKitDevice::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
