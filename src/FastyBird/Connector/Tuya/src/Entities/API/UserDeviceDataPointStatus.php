<?php declare(strict_types = 1);

/**
 * UserDeviceDataPointStatus.php
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
 * OpenAPI device detail status entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class UserDeviceDataPointStatus implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $code,
		private readonly string|int|float|bool $value,
		private readonly string|null $type,
	)
	{
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getValue(): float|bool|int|string
	{
		return $this->value;
	}

	public function getType(): string|null
	{
		return $this->type;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'code' => $this->getCode(),
			'value' => $this->getValue(),
			'type' => $this->getType(),
		];
	}

}
