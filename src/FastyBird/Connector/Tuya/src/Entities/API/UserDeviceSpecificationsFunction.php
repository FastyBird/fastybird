<?php declare(strict_types = 1);

/**
 * UserDeviceSpecificationsFunction.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          0.13.0
 *
 * @date           26.04.22
 */

namespace FastyBird\Connector\Tuya\Entities\API;

use Nette;

/**
 * OpenAPI device specifications function entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class UserDeviceSpecificationsFunction implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $code,
		private readonly string $type,
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
