<?php declare(strict_types = 1);

/**
 * Channel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Devices!
 * @subpackage     Hydrators
 * @since          0.9.0
 *
 * @date           02.01.22
 */

namespace FastyBird\Module\Devices\Hydrators\Properties;

use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Schemas;

/**
 * Channel property entity hydrator
 *
 * @template TEntityClass of Entities\Channels\Properties\Property
 * @extends  Property<TEntityClass>
 *
 * @package        FastyBird:Devices!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Channel extends Property
{

	/** @var Array<string> */
	protected array $relationships
		= [
			Schemas\Channels\Properties\Property::RELATIONSHIPS_CHANNEL,
			Schemas\Channels\Properties\Property::RELATIONSHIPS_PARENT,
		];

}
