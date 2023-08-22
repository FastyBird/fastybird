<?php declare(strict_types = 1);

/**
 * GetDevicesFactoryInfos.php
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
 * OpenAPI get devices factory information entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class GetDevicesFactoryInfos implements Entity
{

	/**
	 * @param array<DeviceFactoryInfos> $result
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceFactoryInfos::class),
		)]
		private readonly array $result,
	)
	{
	}

	/**
	 * @return array<DeviceFactoryInfos>
	 */
	public function getResult(): array
	{
		return $this->result;
	}

	public function toArray(): array
	{
		return [
			'result' => array_map(
				static fn (DeviceFactoryInfos $status): array => $status->toArray(),
				$this->getResult(),
			),
		];
	}

}
