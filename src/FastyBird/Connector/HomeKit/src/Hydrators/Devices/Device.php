<?php declare(strict_types = 1);

/**
 * Device.php
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

namespace FastyBird\Connector\HomeKit\Hydrators\Devices;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * HomeKit device entity hydrator
 *
 * @template  T of Entities\Devices\Device
 * @extends   DevicesHydrators\Devices\Device<T>
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Device extends DevicesHydrators\Devices\Device
{

	/**
	 * @return class-string<Entities\Devices\Device>
	 */
	public function getEntityName(): string
	{
		return Entities\Devices\Device::class;
	}

}
