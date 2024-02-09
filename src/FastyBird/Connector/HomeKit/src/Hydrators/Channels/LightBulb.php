<?php declare(strict_types = 1);

/**
 * LightBulb.php
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

namespace FastyBird\Connector\HomeKit\Hydrators\Channels;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * Light bulb channel entity hydrator
 *
 * @template  T of Entities\Channels\LightBulb
 * @extends   DevicesHydrators\Channels\Channel<T>
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class LightBulb extends DevicesHydrators\Channels\Channel
{

	/**
	 * @return class-string<Entities\Channels\LightBulb>
	 */
	public function getEntityName(): string
	{
		return Entities\Channels\LightBulb::class;
	}

}
