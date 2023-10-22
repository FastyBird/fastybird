<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Connector\Virtual\Hydrators\Devices;

use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Hydrators;

/**
 * Virtual thermostat device entity hydrator
 *
 * @extends Hydrators\VirtualDevice<Entities\Devices\Thermostat>
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Thermostat extends Hydrators\VirtualDevice
{

	public function getEntityName(): string
	{
		return Entities\Devices\Thermostat::class;
	}

}
