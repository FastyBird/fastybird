<?php declare(strict_types = 1);

/**
 * StoreBridgeLog.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           31.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Entities\Messages;

use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Bridge connection state description message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreBridgeLog extends Bridge implements Entity
{

	public function __construct(
		Uuid\UuidInterface $connector,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $level,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $message,
	)
	{
		parent::__construct($connector);
	}

	public function getLevel(): string
	{
		return $this->level;
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'level' => $this->getLevel(),
				'message' => $this->getMessage(),
			],
		);
	}

}
