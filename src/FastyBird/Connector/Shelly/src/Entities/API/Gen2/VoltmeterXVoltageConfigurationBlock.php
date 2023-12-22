<?php declare(strict_types = 1);

/**
 * VoltmeterXVoltageConfigurationBlock.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           21.12.23
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use FastyBird\Connector\Shelly\Entities;
use Orisai\ObjectMapper;

/**
 * Generation 2 device voltage component voltage x configuration entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class VoltmeterXVoltageConfigurationBlock implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('expr')]
		private readonly string|null $expression,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $unit,
	)
	{
	}

	public function getExpression(): string|null
	{
		return $this->expression;
	}

	public function getUnit(): string|null
	{
		return $this->unit;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'expression' => $this->getExpression(),
			'unit' => $this->getUnit(),
		];
	}

}
