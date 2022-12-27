<?php declare(strict_types = 1);

/**
 * SensorRange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 * @since          1.0.0
 *
 * @date           18.07.22
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen1;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;

/**
 * Parsed sensor range entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SensorRange implements Entities\API\Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<string>|array<int>|array<float>|array<int, array<int, (string|null)>>|array<int, (int|null)>|array<int, (float|null)>|array<int, (MetadataTypes\SwitchPayload|string|Types\RelayPayload|null)>|null $format
	 */
	public function __construct(
		private readonly MetadataTypes\DataType $dataType,
		private readonly array|null $format,
		private readonly int|float|string|null $invalid,
	)
	{
	}

	public function getDataType(): MetadataTypes\DataType
	{
		return $this->dataType;
	}

	/**
	 * @return array<string>|array<int>|array<float>|array<int, array<int, (string|null)>>|array<int, (int|null)>|array<int, (float|null)>|array<int, (MetadataTypes\SwitchPayload|string|Types\RelayPayload|null)>|null
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
			'data_type' => $this->dataType->getValue(),
			'format' => $this->format,
			'invalid' => $this->invalid,
		];
	}

}
