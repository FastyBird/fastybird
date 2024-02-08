<?php declare(strict_types = 1);

/**
 * ShellyChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           07.01.23
 */

namespace FastyBird\Connector\Shelly\Entities;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Application\Doctrine\Mapping as ApplicationMapping;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class ShellyChannel extends DevicesEntities\Channels\Channel
{

	public const TYPE = 'shelly-connector';

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): MetadataTypes\Sources\Connector
	{
		return MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::SHELLY);
	}

}
