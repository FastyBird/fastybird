<?php declare(strict_types = 1);

/**
 * ThermostatModeDetect.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           16.10.24
 */

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;

/**
 * NS Panel thermostat mode detect capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\ThermostatModeDetect>
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ThermostatModeDetect extends Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\ThermostatModeDetect::class;
	}

}
