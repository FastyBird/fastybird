<?php declare(strict_types = 1);

/**
 * Variable.php
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

namespace FastyBird\Module\Devices\Hydrators\Channels\Properties;

use FastyBird\Module\Devices\Entities;

/**
 * Channel property entity hydrator
 *
 * @extends Property<Entities\Channels\Properties\Variable>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Variable extends Property
{

	/**
	 * @return class-string<Entities\Channels\Properties\Variable>
	 */
	public function getEntityName(): string
	{
		return Entities\Channels\Properties\Variable::class;
	}

}
