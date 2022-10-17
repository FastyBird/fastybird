<?php declare(strict_types = 1);

/**
 * ChannelMapped.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @since          0.47.0
 *
 * @date           02.04.22
 */

namespace FastyBird\DevicesModule\Hydrators\Properties;

use FastyBird\DevicesModule\Entities;

/**
 * Channel property entity hydrator
 *
 * @extends Channel<Entities\Channels\Properties\Dynamic>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelMapped extends Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\Properties\Mapped::class;
	}

}
