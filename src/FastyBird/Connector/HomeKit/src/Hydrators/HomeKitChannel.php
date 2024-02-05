<?php declare(strict_types = 1);

/**
 * HomeKitChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           24.01.23
 */

namespace FastyBird\Connector\HomeKit\Hydrators;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * HomeKit channel entity hydrator
 *
 * @template  T of Entities\HomeKitChannel
 * @extends   DevicesHydrators\Channels\Channel<T>
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class HomeKitChannel extends DevicesHydrators\Channels\Channel
{

	public function getEntityName(): string
	{
		return Entities\HomeKitChannel::class;
	}

}
