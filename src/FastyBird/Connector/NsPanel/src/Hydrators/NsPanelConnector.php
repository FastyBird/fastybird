<?php declare(strict_types = 1);

/**
 * NsPanelConnector.php
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
 * NS Panel connector entity hydrator
 *
 * @extends DevicesHydrators\Connectors\Connector<Entities\NsPanelConnector>
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class NsPanelConnector extends DevicesHydrators\Connectors\Connector
{

	public function getEntityName(): string
	{
		return Entities\NsPanelConnector::class;
	}

}
