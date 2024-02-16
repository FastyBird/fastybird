<?php declare(strict_types = 1);

/**
 * UserDeviceSpecificationsState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           26.04.22
 */

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;

/**
 * OpenAPI device specifications state message
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class UserDeviceSpecificationsState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $code,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $type,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $values,
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
