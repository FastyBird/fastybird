<?php declare(strict_types = 1);

/**
 * PresetAway.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           20.10.23
 */

namespace FastyBird\Connector\Virtual\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class PresetAway extends Preset
{

	public const TYPE = 'virtual-thermostat-preset-away';

	public function getType(): string
	{
		return self::TYPE;
	}

	public function getDiscriminatorName(): string
	{
		return self::TYPE;
	}

}
