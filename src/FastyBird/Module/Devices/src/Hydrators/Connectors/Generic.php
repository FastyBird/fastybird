<?php declare(strict_types = 1);

/**
 * Generic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           08.04.24
 */

namespace FastyBird\Module\Devices\Hydrators\Connectors;

use FastyBird\Module\Devices\Entities;

/**
 * Generic connector entity hydrator
 *
 * @extends Connector<Entities\Connectors\Generic>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Generic extends Connector
{

	public function getEntityName(): string
	{
		return Entities\Connectors\Generic::class;
	}

}
