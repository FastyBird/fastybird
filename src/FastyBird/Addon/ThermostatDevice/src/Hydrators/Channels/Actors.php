<?php declare(strict_types = 1);

/**
 * Actors.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           23.10.23
 */

namespace FastyBird\Addon\ThermostatDevice\Hydrators\Channels;

use FastyBird\Addon\ThermostatDevice\Entities;
use FastyBird\Addon\ThermostatDevice\Hydrators;

/**
 * Virtual actors channel entity hydrator
 *
 * @extends Hydrators\ThermostatChannel<Entities\Channels\Actors>
 *
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Actors extends Hydrators\ThermostatChannel
{

	public function getEntityName(): string
	{
		return Entities\Channels\Actors::class;
	}

}
