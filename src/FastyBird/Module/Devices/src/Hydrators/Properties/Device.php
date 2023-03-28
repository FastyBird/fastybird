<?php declare(strict_types = 1);

/**
 * Device.php
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

namespace FastyBird\Module\Devices\Hydrators\Properties;

use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Schemas;

/**
 * Device property entity hydrator
 *
 * @template T of Entities\Devices\Properties\Property
 * @extends  Property<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Device extends Property
{

	/** @var array<string> */
	protected array $relationships
		= [
			Schemas\Devices\Properties\Property::RELATIONSHIPS_DEVICE,
			Schemas\Devices\Properties\Property::RELATIONSHIPS_PARENT,
		];

}
