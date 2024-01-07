<?php declare(strict_types = 1);

/**
 * TuyaChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           07.01.24
 */

namespace FastyBird\Connector\Tuya\Hydrators;

use FastyBird\Connector\Tuya\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * Tuya channel entity hydrator
 *
 * @extends DevicesHydrators\Channels\Channel<Entities\TuyaChannel>
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TuyaChannel extends DevicesHydrators\Channels\Channel
{

	public function getEntityName(): string
	{
		return Entities\TuyaChannel::class;
	}

}
