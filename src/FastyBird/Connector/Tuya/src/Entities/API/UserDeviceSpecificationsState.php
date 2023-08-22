<?php declare(strict_types = 1);

/**
 * UserDeviceSpecificationsState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           26.04.22
 */

namespace FastyBird\Connector\Tuya\Entities\API;

use Orisai\ObjectMapper;

/**
 * OpenAPI device specifications state entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class UserDeviceSpecificationsState implements Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $code,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $type,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $values,
	)
	{
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getValues(): string
	{
		return $this->values;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'code' => $this->getCode(),
			'type' => $this->getType(),
			'values' => $this->getValues(),
		];
	}

}
