<?php declare(strict_types = 1);

/**
 * Connector.php
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

namespace FastyBird\Connector\Virtual\Hydrators\Connectors;

use FastyBird\Connector\Virtual\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * Virtual connector entity hydrator
 *
 * @extends DevicesHydrators\Connectors\Connector<Entities\Connectors\Connector>
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector extends DevicesHydrators\Connectors\Connector
{

	public function getEntityName(): string
	{
		return Entities\Connectors\Connector::class;
	}

}
