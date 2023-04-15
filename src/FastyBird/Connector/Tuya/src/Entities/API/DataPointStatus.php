<?php declare(strict_types = 1);

/**
 * DataPointStatus.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           16.11.23
 */

namespace FastyBird\Connector\Tuya\Entities\API;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;
use function is_scalar;
use function strval;

/**
 * Data point status entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DataPointStatus implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $code,
		private readonly float|int|string|bool|MetadataTypes\SwitchPayload|null $value,
		private string|null $dps,
	)
	{
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getValue(): float|int|string|bool|MetadataTypes\SwitchPayload|null
	{
		return $this->value;
	}

	public function getDps(): string|null
	{
		return $this->dps;
	}

	public function setDps(string|null $dps): void
	{
		$this->dps = $dps;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'code' => $this->getCode(),
			'value' => is_scalar($this->getValue()) ? $this->getValue() : strval($this->getValue()),
			'dps' => $this->getDps(),
		];
	}

}
