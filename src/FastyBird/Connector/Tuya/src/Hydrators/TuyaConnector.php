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
use FastyBird\DevicesModule\Hydrators as DevicesModuleHydrators;

/**
 * Tuya connector entity hydrator
 *
 * @phpstan-extends DevicesModuleHydrators\Connectors\Connector<Entities\TuyaConnector>
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TuyaConnector extends DevicesModuleHydrators\Connectors\Connector
{

	public function getEntityName(): string
	{
		return Entities\TuyaConnector::class;
	}

}
