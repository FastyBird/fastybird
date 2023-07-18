<?php declare(strict_types = 1);

/**
 * NsPanelDevice.php
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

namespace FastyBird\Connector\NsPanel\Schemas;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * NS Panel device entity schema
 *
 * @template T of Entities\NsPanelDevice
 * @extends  DevicesSchemas\Devices\Device<T>
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class NsPanelDevice extends DevicesSchemas\Devices\Device
{

}
