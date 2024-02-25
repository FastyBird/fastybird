<?php declare(strict_types = 1);

/**
 * Dynamic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           04.01.22
 */

namespace FastyBird\Module\Devices\Hydrators\Devices\Properties;

use FastyBird\Module\Devices\Entities;

/**
 * Device property entity hydrator
 *
 * @extends Property<Entities\Devices\Properties\Dynamic>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Dynamic extends Property
{

	/**
	 * @return class-string<Entities\Devices\Properties\Dynamic>
	 */
	public function getEntityName(): string
	{
		return Entities\Devices\Properties\Dynamic::class;
	}

}
