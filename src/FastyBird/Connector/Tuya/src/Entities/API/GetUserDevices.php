<?php declare(strict_types = 1);

/**
 * GetUserDevices.php
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
 * OpenAPI get user devices entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class GetUserDevices implements Entity
{

	/**
	 * @param array<UserDevice> $result
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(UserDevice::class),
		)]
		private readonly array $result,
	)
	{
	}

	/**
	 * @return array<UserDevice>
	 */
	public function getResult(): array
	{
		return $this->result;
	}

	public function toArray(): array
	{
		return [
			'result' => array_map(
				static fn (UserDevice $status): array => $status->toArray(),
				$this->getResult(),
			),
		];
	}

}
