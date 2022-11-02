<?php declare(strict_types = 1);

/**
 * ShellyDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Hydrators
 * @since          0.1.0
 *
 * @date           22.01.22
 */

namespace FastyBird\Connector\Shelly\Hydrators;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * Shelly device entity hydrator
 *
 * @extends DevicesHydrators\Devices\Device<Entities\ShellyDevice>
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ShellyDevice extends DevicesHydrators\Devices\Device
{

	public function getEntityName(): string
	{
		return Entities\ShellyDevice::class;
	}

}
