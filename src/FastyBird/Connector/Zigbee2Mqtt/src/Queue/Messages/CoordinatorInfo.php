<?php declare(strict_types = 1);

/**
 * CoordinatorInfo.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           25.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages;

use Orisai\ObjectMapper;

/**
 * Coordinator information
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class CoordinatorInfo implements Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('ieee_address')]
		private string $ieeeAddress,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $type,
	)
	{
	}

	public function getIeeeAddress(): string
	{
		return $this->ieeeAddress;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function toArray(): array
	{
		return [
			'ieee_address' => $this->getIeeeAddress(),
			'type' => $this->getType(),
		];
	}

}
