<?php declare(strict_types = 1);

/**
 * GetDevice.php
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

/**
 * OpenAPI get device detail entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class GetDevice implements Entity
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(Device::class)]
		private readonly Device $result,
	)
	{
	}

	public function getResult(): Device
	{
		return $this->result;
	}

	public function toArray(): array
	{
		return [
			'result' => $this->getResult()->toArray(),
		];
	}

}
