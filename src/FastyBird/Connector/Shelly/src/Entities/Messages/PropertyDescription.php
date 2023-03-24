<?php declare(strict_types = 1);

/**
 * PropertyDescription.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Entities\Messages;

use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;

/**
 * Device or channel property description entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class PropertyDescription implements Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<string>|array<int>|array<float>|array<int, array<int, (string|array<int, string>|null)>>|array<int, (int|null)>|array<int, (float|null)>|array<int, (MetadataTypes\SwitchPayload|string|Types\RelayPayload|null)>|null $format
	 */
	public function __construct(
		private readonly string $identifier,
		private readonly MetadataTypes\DataType $dataType,
		private readonly string|null $unit = null,
		private readonly array|null $format = null,
		private readonly float|int|string|null $invalid = null,
		private readonly bool $queryable = false,
		private readonly bool $settable = false,
	)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getDataType(): MetadataTypes\DataType
	{
		return $this->dataType;
	}

	public function getUnit(): string|null
	{
		return $this->unit;
	}

	/**
	 * @return array<string>|array<int>|array<float>|array<int, array<int, (string|array<int, string>|null)>>|array<int, (int|null)>|array<int, (float|null)>|array<int, (MetadataTypes\SwitchPayload|string|Types\RelayPayload|null)>|null
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
			'identifier' => $this->getIdentifier(),
			'data_type' => $this->getDataType()->getValue(),
			'unit' => $this->getUnit(),
			'format' => $this->getFormat(),
			'invalid' => $this->getInvalid(),
			'queryable' => $this->isQueryable(),
			'settable' => $this->isQueryable(),
		];
	}

}
