<?php declare(strict_types = 1);

/**
 * GetDeviceState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           13.08.23
 */

namespace FastyBird\Connector\Tuya\Entities\API;

use Orisai\ObjectMapper;
use function array_map;

/**
 * OpenAPI get device state entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class GetDeviceState implements Entity
{

	/**
	 * @param array<DeviceDataPointState> $result
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceDataPointState::class),
		)]
		private readonly array $result,
	)
	{
	}

	/**
	 * @return array<DeviceDataPointState>
	 */
	public function getResult(): array
	{
		return $this->result;
	}

	public function toArray(): array
	{
		return [
			'result' => array_map(
				static fn (DeviceDataPointState $status): array => $status->toArray(),
				$this->getResult(),
			),
		];
	}

}
