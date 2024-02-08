<?php declare(strict_types = 1);

/**
 * DeviceVariable.php
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

namespace FastyBird\Module\Devices\Hydrators\Properties;

use FastyBird\Module\Devices\Entities;

/**
 * Device property entity hydrator
 *
 * @extends Device<Entities\Devices\Properties\Variable>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceVariable extends Device
{

	/**
	 * @return class-string<Entities\Devices\Properties\Variable>
	 */
	public function getEntityName(): string
	{
		return Entities\Devices\Properties\Variable::class;
	}

}
