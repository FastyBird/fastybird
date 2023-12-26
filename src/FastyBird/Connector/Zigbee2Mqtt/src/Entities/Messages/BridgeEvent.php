<?php declare(strict_types = 1);

/**
 * BridgeEvent.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           24.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Entities\Messages;

use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Bridge event message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class BridgeEvent extends Bridge implements Entity
{

	public function __construct(
		Uuid\UuidInterface $connector,
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\BridgeEvent::class)]
		private readonly Types\BridgeEvent $type,
		#[ObjectMapper\Rules\MappedObjectValue(EventData::class)]
		private readonly EventData $data,
	)
	{
		parent::__construct($connector);
	}

	public function getType(): Types\BridgeEvent
	{
		return $this->type;
	}

	public function getData(): EventData
	{
		return $this->data;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'type' => $this->getType()->getValue(),
				'data' => $this->getData()->toArray(),
			],
		);
	}

}
