<?php declare(strict_types = 1);

/**
 * NsPanelChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           21.07.23
 */

namespace FastyBird\Connector\NsPanel\Hydrators;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * NS Panel channel entity hydrator
 *
 * @extends DevicesHydrators\Channels\Channel<Entities\NsPanelChannel>
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class NsPanelChannel extends DevicesHydrators\Channels\Channel
{

	public function getEntityName(): string
	{
		return Entities\NsPanelChannel::class;
	}

}
