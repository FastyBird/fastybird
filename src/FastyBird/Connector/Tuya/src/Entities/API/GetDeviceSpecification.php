<?php declare(strict_types = 1);

/**
 * GetDeviceSpecification.php
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

/**
 * OpenAPI get device specification entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class GetDeviceSpecification implements Entity
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(DeviceSpecification::class)]
		private readonly DeviceSpecification $result,
	)
	{
	}

	public function getResult(): DeviceSpecification
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
