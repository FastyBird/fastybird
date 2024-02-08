<?php declare(strict_types = 1);

/**
 * ConnectorDynamic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           08.02.22
 */

namespace FastyBird\Module\Devices\Hydrators\Properties;

use FastyBird\Module\Devices\Entities;

/**
 * Connector property entity hydrator
 *
 * @extends Connector<Entities\Connectors\Properties\Dynamic>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorDynamic extends Connector
{

	/**
	 * @return class-string<Entities\Connectors\Properties\Dynamic>
	 */
	public function getEntityName(): string
	{
		return Entities\Connectors\Properties\Dynamic::class;
	}

}
