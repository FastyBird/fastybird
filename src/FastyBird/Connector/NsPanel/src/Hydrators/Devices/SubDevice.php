<?php declare(strict_types = 1);

/**
 * SubDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           12.07.23
 */

namespace FastyBird\Connector\NsPanel\Hydrators\Devices;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Hydrators;

/**
 * NS Panel sub-device entity hydrator
 *
 * @extends Hydrators\NsPanelDevice<Entities\Devices\SubDevice>
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SubDevice extends Hydrators\NsPanelDevice
{

	public function getEntityName(): string
	{
		return Entities\Devices\SubDevice::class;
	}

}
