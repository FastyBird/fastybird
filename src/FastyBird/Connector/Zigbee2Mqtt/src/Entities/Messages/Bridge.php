<?php declare(strict_types = 1);

/**
 * Bridge.php
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

use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Bridge message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Bridge implements Entity
{

	public function __construct(
		#[BootstrapObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $connector,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('base_topic')]
		private readonly string $baseTopic,
	)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getBaseTopic(): string
	{
		return $this->baseTopic;
	}

	public function toArray(): array
	{
		return [
			'connector' => $this->getConnector()->toString(),
			'base_topic' => $this->getBaseTopic(),
		];
	}

}
