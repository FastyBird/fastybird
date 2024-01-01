<?php declare(strict_types = 1);

/**
 * StoreBridgeDevices.php
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
use Ramsey\Uuid;
use function array_map;
use function array_merge;

/**
 * Bridge group description message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreBridgeDevices extends Bridge implements Entity
{

	/**
	 * @param array<DeviceDescription> $devices
	 */
	public function __construct(
		Uuid\UuidInterface $connector,
		string $baseTopic,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(class: DeviceDescription::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private readonly array $devices,
	)
	{
		parent::__construct($connector, $baseTopic);
	}

	/**
	 * @return array<DeviceDescription>
	 */
	public function getDevices(): array
	{
		return $this->devices;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'device' => array_map(
					static fn (DeviceDescription $device): array => $device->toArray(),
					$this->getDevices(),
				),
			],
		);
	}

}
