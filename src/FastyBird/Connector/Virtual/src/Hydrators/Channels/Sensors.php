<?php declare(strict_types = 1);

/**
 * Sensors.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           23.10.23
 */

namespace FastyBird\Connector\Virtual\Hydrators\Channels;

use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Hydrators;

/**
 * Virtual sensors channel entity hydrator
 *
 * @extends Hydrators\VirtualChannel<Entities\Channels\Sensors>
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Sensors extends Hydrators\VirtualChannel
{

	public function getEntityName(): string
	{
		return Entities\Channels\Sensors::class;
	}

}
