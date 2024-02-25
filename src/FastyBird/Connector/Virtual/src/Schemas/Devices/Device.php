<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Connector\Virtual\Schemas\Devices;

use FastyBird\Connector\Virtual\Entities;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Virtual device entity schema
 *
 * @template T of Entities\Devices\Device
 * @extends  DevicesSchemas\Devices\Device<T>
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Device extends DevicesSchemas\Devices\Device
{

}
