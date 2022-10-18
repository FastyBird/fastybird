<?php declare(strict_types = 1);

/**
 * Blank.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @since          0.6.0
 *
 * @date           07.12.21
 */

namespace FastyBird\Module\Devices\Hydrators\Connectors;

use FastyBird\Module\Devices\Entities;

/**
 * Blank connector entity hydrator
 *
 * @extends Connector<Entities\Connectors\Blank>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Blank extends Connector
{

	public function getEntityName(): string
	{
		return Entities\Connectors\Blank::class;
	}

}
