<?php declare(strict_types = 1);

/**
 * ChannelDynamic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Devices!
 * @subpackage     Hydrators
 * @since          0.9.0
 *
 * @date           04.01.22
 */

namespace FastyBird\Module\Devices\Hydrators\Properties;

use FastyBird\Module\Devices\Entities;

/**
 * Channel property entity hydrator
 *
 * @extends Channel<Entities\Channels\Properties\Dynamic>
 *
 * @package        FastyBird:Devices!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelDynamic extends Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\Properties\Dynamic::class;
	}

}
