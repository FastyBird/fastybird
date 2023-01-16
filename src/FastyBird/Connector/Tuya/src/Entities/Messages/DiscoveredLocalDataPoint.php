<?php declare(strict_types = 1);

/**
 * DiscoveredLocalDataPoint.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           29.08.22
 */

namespace FastyBird\Connector\Tuya\Entities\Messages;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;

/**
 * Discovered cloud device data point entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DiscoveredLocalDataPoint implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $device,
		private readonly string $code,
		private readonly string $name,
		private readonly MetadataTypes\DataType $dataType,
		private readonly string|null $unit,
		private readonly string|null $format,
		private readonly int|float|null $min,
		private readonly int|float|null $max,
		private readonly int|float|null $step,
		private readonly bool $queryable,
		private readonly bool $settable,
	)
	{
	}

	public function getDevice(): string
	{
		return $this->device;
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDataType(): MetadataTypes\DataType
	{
		return $this->dataType;
	}

	public function getUnit(): string|null
	{
		return $this->unit;
	}

	public function getFormat(): string|null
	{
		return $this->format;
	}

	public function getMin(): float|int|null
	{
		return $this->min;
	}

	public function getMax(): float|int|null
	{
		return $this->max;
	}

	public function getStep(): float|int|null
	{
		return $this->step;
	}

	public function isQueryable(): bool
	{
		return $this->queryable;
	}

	public function isSettable(): bool
	{
		return $this->settable;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'device' => $this->getDevice(),

			'code' => $this->getCode(),
			'name' => $this->getName(),
			'data_type' => $this->getDataType()->getValue(),
			'unit' => $this->getUnit(),
			'format' => $this->getFormat(),
			'min' => $this->getMin(),
			'max' => $this->getMax(),
			'step' => $this->getStep(),
			'queryable' => $this->isQueryable(),
			'settable' => $this->isSettable(),
		];
	}

}
