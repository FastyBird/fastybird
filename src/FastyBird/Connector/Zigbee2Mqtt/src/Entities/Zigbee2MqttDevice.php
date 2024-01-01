<?php declare(strict_types = 1);

/**
 * Zigbee2MqttDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           23.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Entities;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;

/**
 * @ORM\Entity
 */
class Zigbee2MqttDevice extends DevicesEntities\Devices\Device
{

	public const TYPE = 'zigbee2mqtt';

	/**
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="string", name="device_identifier", length=50, nullable=false)
	 */
	protected string $identifier;

	public function getType(): string
	{
		return self::TYPE;
	}

	public function getDiscriminatorName(): string
	{
		return self::TYPE;
	}

	public function getSource(): MetadataTypes\ConnectorSource
	{
		return MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT);
	}

}
