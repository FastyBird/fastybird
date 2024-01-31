<?php declare(strict_types = 1);

/**
 * LightBulb.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           30.01.24
 */

namespace FastyBird\Connector\HomeKit\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\HomeKit\Entities;

/**
 * @ORM\Entity
 */
class LightBulb extends Entities\HomeKitChannel
{

	public const TYPE = 'homekit-connector-light-bulb';

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getDiscriminatorName(): string
	{
		return self::TYPE;
	}

}
