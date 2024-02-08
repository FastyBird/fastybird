<?php declare(strict_types = 1);

/**
 * FbMqttChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           07.01.23
 */

namespace FastyBird\Connector\FbMqtt\Entities;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Application\Doctrine\Mapping as ApplicationMapping;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class FbMqttChannel extends DevicesEntities\Channels\Channel
{

	public const TYPE = 'fb-mqtt-connector';

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): MetadataTypes\Sources\Connector
	{
		return MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::FB_MQTT);
	}

}
