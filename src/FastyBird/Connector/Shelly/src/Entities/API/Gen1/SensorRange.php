<?php declare(strict_types = 1);

/**
 * SensorRange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           18.07.22
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen1;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Orisai\ObjectMapper;

/**
 * Parsed sensor range entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SensorRange implements Entities\API\Entity
{

	/**
	 * @param array<string>|array<int>|array<float>|array<int, array<int, (array<int, bool|string>|null)>>|null $format
	 */
	public function __construct(
		#[ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: MetadataTypes\DataType::class)]
		#[ObjectMapper\Modifiers\FieldName('data_type')]
		private readonly MetadataTypes\DataType $dataType,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayOf(new ObjectMapper\Rules\StringValue(notEmpty: true)),
			new ObjectMapper\Rules\ArrayOf(new ObjectMapper\Rules\IntValue()),
			new ObjectMapper\Rules\ArrayOf(new ObjectMapper\Rules\FloatValue()),
			new ObjectMapper\Rules\ArrayOf(
				new ObjectMapper\Rules\ArrayOf(
					new ObjectMapper\Rules\AnyOf([
						new ObjectMapper\Rules\ArrayOf(
							new ObjectMapper\Rules\AnyOf([
								new ObjectMapper\Rules\BoolValue(),
								new ObjectMapper\Rules\StringValue(notEmpty: true),
								new ObjectMapper\Rules\NullValue(castEmptyString: true),
							]),
							new ObjectMapper\Rules\IntValue(),
						),
						new ObjectMapper\Rules\NullValue(),
					]),
					new ObjectMapper\Rules\IntValue(),
				),
				new ObjectMapper\Rules\IntValue(),
			),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly array|null $format,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly int|float|string|null $invalid,
	)
	{
	}

	public function getDataType(): MetadataTypes\DataType
	{
		return $this->dataType;
	}

	/**
	 * @return array<string>|array<int>|array<float>|array<int, array<int, (array<int, bool|string>|null)>>|null
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
			'format' => $this->getFormat(),
			'invalid' => $this->getInvalid(),
		];
	}

}
