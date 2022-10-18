<?php declare(strict_types = 1);

/**
 * FbMqttConnector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Entities
 * @since          0.4.0
 *
 * @date           23.01.22
 */

namespace FastyBird\Connector\FbMqtt\Entities;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\DevicesModule\Entities as DevicesModuleEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;

/**
 * @ORM\Entity
 */
class FbMqttConnector extends DevicesModuleEntities\Connectors\Connector
{

	public const CONNECTOR_TYPE = 'fb-mqtt';

	public function getType(): string
	{
		return self::CONNECTOR_TYPE;
	}

	public function getDiscriminatorName(): string
	{
		return self::CONNECTOR_TYPE;
	}

	public function getSource(): MetadataTypes\ConnectorSource
	{
		return MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT);
	}

}
