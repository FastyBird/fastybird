<?php declare(strict_types = 1);

/**
 * NsPanelDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Hydrators;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * NS Panel device entity hydrator
 *
 * @extends DevicesHydrators\Devices\Device<Entities\NsPanelDevice>
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class NsPanelDevice extends DevicesHydrators\Devices\Device
{

	public function getEntityName(): string
	{
		return Entities\NsPanelDevice::class;
	}

}
