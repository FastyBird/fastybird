<?php declare(strict_types = 1);

/**
 * Blank.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           07.12.21
 */

namespace FastyBird\Module\Devices\Entities\Connectors;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Module\Devices\Entities;

/**
 * @ORM\Entity
 */
class Blank extends Entities\Connectors\Connector
{

	public const CONNECTOR_TYPE = 'blank';

	public function getType(): string
	{
		return self::CONNECTOR_TYPE;
	}

	public function getDiscriminatorName(): string
	{
		return self::CONNECTOR_TYPE;
	}

}
