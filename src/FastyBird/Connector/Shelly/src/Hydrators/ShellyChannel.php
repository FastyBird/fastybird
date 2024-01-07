<?php declare(strict_types = 1);

/**
 * ShellyChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           07.01.24
 */

namespace FastyBird\Connector\Shelly\Hydrators;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * Shelly channel entity hydrator
 *
 * @extends DevicesHydrators\Channels\Channel<Entities\ShellyChannel>
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ShellyChannel extends DevicesHydrators\Channels\Channel
{

	public function getEntityName(): string
	{
		return Entities\ShellyChannel::class;
	}

}
