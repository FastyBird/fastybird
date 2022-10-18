<?php declare(strict_types = 1);

/**
 * TuyaDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Hydrators
 * @since          0.1.0
 *
 * @date           25.04.22
 */

namespace FastyBird\Connector\Tuya\Hydrators;

use FastyBird\Connector\Tuya\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * Tuya device entity hydrator
 *
 * @phpstan-extends DevicesHydrators\Devices\Device<Entities\TuyaDevice>
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TuyaDevice extends DevicesHydrators\Devices\Device
{

	public function getEntityName(): string
	{
		return Entities\TuyaDevice::class;
	}

}
