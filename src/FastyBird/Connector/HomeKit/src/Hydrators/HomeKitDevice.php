<?php declare(strict_types = 1);

/**
 * HomeKitDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           29.03.22
 */

namespace FastyBird\Connector\HomeKit\Hydrators;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Entities\Devices\Device;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * HomeKit device entity hydrator
 *
 * @template  T of Device
 * @extends   DevicesHydrators\Devices\Device<T>
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class HomeKitDevice extends DevicesHydrators\Devices\Device
{

	/**
	 * @return class-string<Device>
	 */
	public function getEntityName(): string
	{
		return Entities\Devices\Device::class;
	}

}
