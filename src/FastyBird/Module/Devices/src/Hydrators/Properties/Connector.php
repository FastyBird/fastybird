<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @since          0.31.0
 *
 * @date           08.02.22
 */

namespace FastyBird\Module\Devices\Hydrators\Properties;

use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Schemas;

/**
 * Connector property entity hydrator
 *
 * @template T of Entities\Connectors\Properties\Property
 * @extends  Property<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Connector extends Property
{

	/** @var Array<string> */
	protected array $relationships
		= [
			Schemas\Connectors\Properties\Property::RELATIONSHIPS_CONNECTOR,
		];

}
