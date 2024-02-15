<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           02.01.22
 */

namespace FastyBird\Module\Devices\Hydrators\Channels\Properties;

use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Hydrators;
use FastyBird\Module\Devices\Schemas;

/**
 * Channel property entity hydrator
 *
 * @template T of Entities\Channels\Properties\Property
 * @extends  Hydrators\Property<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Property extends Hydrators\Property
{

	/** @var array<string> */
	protected array $relationships
		= [
			Schemas\Channels\Properties\Property::RELATIONSHIPS_CHANNEL,
			Schemas\Channels\Properties\Property::RELATIONSHIPS_PARENT,
		];

}
