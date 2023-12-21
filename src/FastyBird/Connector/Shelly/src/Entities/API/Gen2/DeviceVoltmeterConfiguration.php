<?php declare(strict_types = 1);

/**
 * DeviceVoltmeterConfiguration.php
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

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Types;
use Orisai\ObjectMapper;

/**
 * Generation 2 device voltmeter configuration entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceVoltmeterConfiguration implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private readonly int $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $name,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('report_thr')]
		private readonly float|null $reportThreshold,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: VoltmeterXVoltageConfigurationBlock::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly VoltmeterXVoltageConfigurationBlock|null $xvoltage,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::get(Types\ComponentType::VOLTMETER);
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getReportThreshold(): float|null
	{
		return $this->reportThreshold;
	}

	public function getXvoltage(): VoltmeterXVoltageConfigurationBlock|null
	{
		return $this->xvoltage;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'type' => $this->getType()->getValue(),
			'name' => $this->getName(),
			'report_threshold' => $this->getReportThreshold(),
			'xvoltage' => $this->getXvoltage()?->toArray(),
		];
	}

}
