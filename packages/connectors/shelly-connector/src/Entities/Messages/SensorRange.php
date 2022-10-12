<?php declare(strict_types = 1);

/**
 * SensorRange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 * @since          0.37.0
 *
 * @date           18.07.22
 */

namespace FastyBird\ShellyConnector\Entities\Messages;

use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\ShellyConnector\Types;
use Nette;

/**
 * Parsed sensor range entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SensorRange implements Entity
{

	use Nette\SmartObject;

	/**
	 * @param Array<string>|Array<int>|Array<float>|Array<int, Array<int, (string|null)>>|Array<int, (int|null)>|Array<int, (float|null)>|Array<int, (MetadataTypes\SwitchPayload|string|Types\RelayPayload|null)>|null $format
	 */
	public function __construct(
		private readonly Types\MessageSource $source,
		private readonly MetadataTypes\DataType $dataType,
		private readonly array|null $format,
		private readonly int|float|string|null $invalid,
	)
	{
	}

	public function getSource(): Types\MessageSource
	{
		return $this->source;
	}

	public function getDataType(): MetadataTypes\DataType
	{
		return $this->dataType;
	}

	/**
	 * @return Array<string>|Array<int>|Array<float>|Array<int, Array<int, (string|null)>>|Array<int, (int|null)>|Array<int, (float|null)>|Array<int, (MetadataTypes\SwitchPayload|string|Types\RelayPayload|null)>|null
	 */
	public function getFormat(): array|null
	{
		return $this->format;
	}

	public function getInvalid(): float|int|string|null
	{
		return $this->invalid;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'source' => $this->getSource()->getValue(),
			'data_type' => $this->dataType->getValue(),
			'format' => $this->format,
			'invalid' => $this->invalid,
		];
	}

}
