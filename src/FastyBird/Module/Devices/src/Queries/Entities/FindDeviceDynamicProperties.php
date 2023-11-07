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
 * @date           05.08.20
 */

namespace FastyBird\Module\Devices\Queries\Entities;

use FastyBird\Module\Devices\Entities;

/**
 * Find device dynamic properties entities query
 *
 * @template T of Entities\Devices\Properties\Dynamic
 * @extends  FindDeviceProperties<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindDeviceDynamicProperties extends FindDeviceProperties
{

}
