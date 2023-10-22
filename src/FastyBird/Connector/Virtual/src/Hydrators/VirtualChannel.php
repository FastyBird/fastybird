<?php declare(strict_types = 1);

/**
 * VirtualChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           20.10.23
 */

namespace FastyBird\Connector\Virtual\Hydrators;

use FastyBird\Connector\Virtual\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * Virtual channel entity hydrator
 *
 * @extends DevicesHydrators\Channels\Channel<Entities\VirtualChannel>
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class VirtualChannel extends DevicesHydrators\Channels\Channel
{

	public function getEntityName(): string
	{
		return Entities\VirtualChannel::class;
	}

}
