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

namespace FastyBird\ShellyConnector\Hydrators;

use FastyBird\DevicesModule\Hydrators as DevicesModuleHydrators;
use FastyBird\ShellyConnector\Entities;

/**
 * Shelly device entity hydrator
 *
 * @phpstan-extends DevicesModuleHydrators\Devices\Device<Entities\ShellyDevice>
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ShellyDevice extends DevicesModuleHydrators\Devices\Device
{

	public function getEntityName(): string
	{
		return Entities\ShellyDevice::class;
	}

}
