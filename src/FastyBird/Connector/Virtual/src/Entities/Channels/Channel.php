<?php declare(strict_types = 1);

/**
 * Channel.php
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
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;

#[ORM\MappedSuperclass]
abstract class Channel extends DevicesEntities\Channels\Channel
{

	public function getSource(): MetadataTypes\Sources\Source
	{
		return MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::VIRTUAL);
	}

}
