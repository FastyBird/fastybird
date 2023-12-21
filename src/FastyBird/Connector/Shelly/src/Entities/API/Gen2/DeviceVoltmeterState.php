<?php declare(strict_types = 1);

/**
 * DeviceVoltmeterState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           20.12.23
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Types;
use Orisai\ObjectMapper;
use function array_filter;
use function array_merge;

/**
 * Generation 2 device voltage state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceVoltmeterState extends DeviceState implements Entities\API\Entity
{

	/**
	 * @param array<string> $errors
	 */
	public function __construct(
		int $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Shelly\Constants::VALUE_NOT_AVAILABLE]),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly float|string|null $voltage,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Shelly\Constants::VALUE_NOT_AVAILABLE]),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly float|string|null $xvoltage,
		array $errors = [],
	)
	{
		parent::__construct($id, $errors);
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::get(Types\ComponentType::VOLTMETER);
	}

	public function getVoltage(): float|string|null
	{
		return $this->voltage;
	}

	public function getXvoltage(): float|string|null
	{
		return $this->xvoltage;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'voltage' => $this->getVoltage(),
				'xvoltage' => $this->getXvoltage(),
			],
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toState(): array
	{
		return array_filter(
			array_merge(
				parent::toState(),
				[
					'voltage' => $this->getVoltage(),
					'xvoltage' => $this->getXvoltage(),
				],
			),
			static fn ($value): bool => $value !== Shelly\Constants::VALUE_NOT_AVAILABLE,
		);
	}

}
