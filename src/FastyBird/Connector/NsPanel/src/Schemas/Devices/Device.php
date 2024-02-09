<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Schemas\Devices;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * NS Panel device entity schema
 *
 * @template T of Entities\Devices\Device
 * @extends  DevicesSchemas\Devices\Device<T>
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Device extends DevicesSchemas\Devices\Device
{

}
