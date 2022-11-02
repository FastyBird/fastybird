<?php declare(strict_types = 1);

/**
 * Tuya.php
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
 * Tuya connector entity hydrator
 *
 * @extends DevicesHydrators\Connectors\Connector<Entities\TuyaConnector>
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TuyaConnector extends DevicesHydrators\Connectors\Connector
{

	public function getEntityName(): string
	{
		return Entities\TuyaConnector::class;
	}

}
