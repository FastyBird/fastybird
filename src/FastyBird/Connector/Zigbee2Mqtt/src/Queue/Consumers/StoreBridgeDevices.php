<?php declare(strict_types = 1);

/**
 * StoreBridgeDevices.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           01.01.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Consumers;

use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;

/**
 * Store bridge devices message consumer
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreBridgeDevices implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(private readonly Zigbee2Mqtt\Logger $logger)
	{
	}

	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\StoreBridgeDevices) {
			return false;
		}

		$this->logger->debug(
			'Consumed bridge devices list message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT,
				'type' => 'store-bridge-devices-message-consumer',
				'connector' => [
					'id' => $entity->getConnector()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
