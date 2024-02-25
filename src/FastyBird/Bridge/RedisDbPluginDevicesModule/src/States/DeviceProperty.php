<?php declare(strict_types = 1);

/**
 * DeviceProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPluginDevicesModuleBridge!
 * @subpackage     States
 * @since          1.0.0
 *
 * @date           20.10.22
 */

namespace FastyBird\Bridge\RedisDbPluginDevicesModule\States;

use FastyBird\Module\Devices\States as DevicesStates;

/**
 * Device property state
 *
 * @package        FastyBird:RedisDbPluginDevicesModuleBridge!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DeviceProperty extends Property implements DevicesStates\DeviceProperty
{

}
