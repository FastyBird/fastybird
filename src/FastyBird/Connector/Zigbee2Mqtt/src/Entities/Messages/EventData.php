<?php declare(strict_types = 1);

/**
 * EventData.php
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
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use Orisai\ObjectMapper;
use function array_merge;

/**
 * Event data message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class EventData implements Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('friendly_name')]
		private readonly string $friendlyName,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('ieee_address')]
		private readonly string $ieeeAddress,
		#[ObjectMapper\Rules\AnyOf([
			new ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\BridgeEvent::class),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly Types\EventStatus|null $status = null,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool|null $supported = null,
	)
	{
	}

	public function getFriendlyName(): string
	{
		return $this->friendlyName;
	}

	public function getIeeeAddress(): string
	{
		return $this->ieeeAddress;
	}

	public function getStatus(): Types\EventStatus|null
	{
		return $this->status;
	}

	public function getSupported(): bool|null
	{
		return $this->supported;
	}

	public function toArray(): array
	{
		return array_merge(
			[
				'friendly_name' => $this->getFriendlyName(),
				'ieee_address' => $this->getIeeeAddress(),
			],
			$this->getStatus() !== null
				? ['status' => $this->getStatus()->getValue()]
				: [],
			$this->getStatus() !== null && $this->getStatus()->equalsValue(Types\EventStatus::SUCCESSFUL)
				? ['supported' => $this->getSupported()]
				: [],
		);
	}

}
