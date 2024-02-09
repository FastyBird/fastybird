<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           30.01.22
 */

namespace FastyBird\Connector\Modbus\Hydrators\Devices;

use FastyBird\Connector\Modbus\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * Modbus device entity hydrator
 *
 * @extends DevicesHydrators\Devices\Device<Entities\Devices\Device>
 *
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device extends DevicesHydrators\Devices\Device
{

	public function getEntityName(): string
	{
		return Entities\Devices\Device::class;
	}

}
