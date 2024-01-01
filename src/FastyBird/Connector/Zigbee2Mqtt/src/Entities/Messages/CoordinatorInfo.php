<?php declare(strict_types = 1);

/**
 * CoordinatorInfo.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Entities\Messages;

use Orisai\ObjectMapper;

/**
 * Coordinator information
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CoordinatorInfo implements Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('ieee_address')]
		private readonly string $ieeeAddress,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $type,
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
