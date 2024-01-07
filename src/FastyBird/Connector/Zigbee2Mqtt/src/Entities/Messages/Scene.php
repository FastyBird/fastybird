<?php declare(strict_types = 1);

/**
 * Scene.php
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

use Orisai\ObjectMapper;

/**
 * Bridge group scene description message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Scene implements Entity
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private readonly string $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $name,
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
		];
	}

}
