<?php declare(strict_types = 1);

/**
 * DeviceDynamic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @since          0.9.0
 *
 * @date           04.01.22
 */

namespace FastyBird\DevicesModule\Hydrators\Properties;

use FastyBird\DevicesModule\Entities;

/**
 * Device property entity hydrator
 *
 * @extends Device<Entities\Devices\Properties\Dynamic>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceDynamic extends Device
{

	public function getEntityName(): string
	{
		return Entities\Devices\Properties\Dynamic::class;
	}

}
