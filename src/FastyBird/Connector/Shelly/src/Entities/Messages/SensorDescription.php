<?php declare(strict_types = 1);

/**
 * SensorDescription.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 * @since          0.37.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Entities\Messages;

use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;

/**
 * Block sensor description entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SensorDescription implements Entity
{

	use Nette\SmartObject;

	/**
	 * @param Array<string>|Array<int>|Array<float>|Array<int, Array<int, (string|null)>>|Array<int, (int|null)>|Array<int, (float|null)>|Array<int, (MetadataTypes\SwitchPayload|string|Types\RelayPayload|null)>|null $format
	 */
	public function __construct(
		private readonly Types\MessageSource $source,
		private readonly int $identifier,
		private readonly Types\SensorType $type,
		private readonly string $description,
		private readonly MetadataTypes\DataType $dataType,
		private readonly Types\SensorUnit|null $unit = null,
		private readonly array|null $format = null,
		private readonly float|int|string|null $invalid = null,
		private readonly bool $queryable = false,
		private readonly bool $settable = false,
	)
	{
	}

	public function getSource(): Types\MessageSource
	{
		return $this->source;
	}

	public function getIdentifier(): int
	{
		return $this->identifier;
	}

	public function getType(): Types\SensorType
	{
		return $this->type;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function getDataType(): MetadataTypes\DataType
	{
		return $this->dataType;
	}

	public function getUnit(): Types\SensorUnit|null
	{
		return $this->unit;
	}

	/**
	 * @return Array<string>|Array<int>|Array<float>|Array<int, Array<int, (string|null)>>|Array<int, (int|null)>|Array<int, (float|null)>|Array<int, (MetadataTypes\SwitchPayload|string|Types\RelayPayload|null)>|null
	 */
	public function getFormat(): mixed
	{
		return $this->format;
	}

	public function getInvalid(): float|int|string|null
	{
		return $this->invalid;
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
			'source' => $this->getSource()->getValue(),
			'identifier' => $this->getIdentifier(),
			'type' => $this->getType()->getValue(),
			'description' => $this->getDescription(),
			'data_type' => $this->getDataType()->getValue(),
			'unit' => $this->getUnit()?->getValue(),
			'format' => $this->getFormat(),
			'invalid' => $this->getInvalid(),
			'queryable' => $this->isQueryable(),
			'settable' => $this->isQueryable(),
		];
	}

}
