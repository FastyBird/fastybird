<?php declare(strict_types = 1);

/**
 * GetUserDeviceSpecifications.php
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
 * OpenAPI get user device specifications entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class GetUserDeviceSpecifications implements Entity
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(UserDeviceSpecifications::class)]
		private readonly UserDeviceSpecifications $result,
	)
	{
	}

	public function getResult(): UserDeviceSpecifications
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
