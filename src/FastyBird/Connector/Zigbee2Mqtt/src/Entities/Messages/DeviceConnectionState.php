<?php declare(strict_types = 1);

/**
 * DeviceConnectionState.php
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
use Ramsey\Uuid;
use function array_merge;

/**
 * Device connection state description message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceConnectionState extends Device implements Entity
{

	public function __construct(
		Uuid\UuidInterface $connector,
		string $device,
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\ConnectionState::class)]
		private readonly Types\ConnectionState $state,
	)
	{
		parent::__construct($connector, $device);
	}

	public function getState(): Types\ConnectionState
	{
		return $this->state;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'state' => $this->getState()->getValue(),
			],
		);
	}

}
