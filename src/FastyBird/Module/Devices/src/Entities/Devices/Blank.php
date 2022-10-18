<?php declare(strict_types = 1);

/**
 * Blank.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Devices!
 * @subpackage     Entities
 * @since          0.9.0
 *
 * @date           07.01.22
 */

namespace FastyBird\Module\Devices\Entities\Devices;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Module\Devices\Entities;

/**
 * @ORM\Entity
 */
class Blank extends Entities\Devices\Device
{

	public const DEVICE_TYPE = 'blank';

	public function getType(): string
	{
		return self::DEVICE_TYPE;
	}

	public function getDiscriminatorName(): string
	{
		return self::DEVICE_TYPE;
	}

}
