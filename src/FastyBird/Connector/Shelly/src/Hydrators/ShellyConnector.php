<?php declare(strict_types = 1);

/**
 * ShellyConnector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           22.01.22
 */

namespace FastyBird\Connector\Shelly\Hydrators;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * Shelly connector entity hydrator
 *
 * @extends DevicesHydrators\Connectors\Connector<Entities\ShellyConnector>
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ShellyConnector extends DevicesHydrators\Connectors\Connector
{

	public function getEntityName(): string
	{
		return Entities\ShellyConnector::class;
	}

}
