<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Connector\Virtual\Hydrators\Devices;

use FastyBird\Connector\Virtual\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * Virtual device entity hydrator
 *
 * @template  T of Entities\Devices\Device
 * @extends   DevicesHydrators\Devices\Device<T>
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Device extends DevicesHydrators\Devices\Device
{

}
