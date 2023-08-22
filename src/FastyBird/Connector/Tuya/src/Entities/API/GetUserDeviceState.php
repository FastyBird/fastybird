<?php declare(strict_types = 1);

/**
 * GetUserDeviceState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           14.08.23
 */

namespace FastyBird\Connector\Tuya\Entities\API;

use Orisai\ObjectMapper;
use function array_map;

/**
 * OpenAPI get user device state entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class GetUserDeviceState implements Entity
{

	/**
	 * @param array<UserDeviceDataPointState> $result
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(UserDeviceDataPointState::class),
		)]
		private readonly array $result,
	)
	{
	}

	/**
	 * @return array<UserDeviceDataPointState>
	 */
	public function getResult(): array
	{
		return $this->result;
	}

	public function toArray(): array
	{
		return [
			'result' => array_map(
				static fn (UserDeviceDataPointState $status): array => $status->toArray(),
				$this->getResult(),
			),
		];
	}

}
