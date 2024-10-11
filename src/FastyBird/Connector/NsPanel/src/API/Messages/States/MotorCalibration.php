<?php declare(strict_types = 1);

/**
 * MotorCalibration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Motor calibration detection capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class MotorCalibration implements State
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\MotorCalibration::class)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::MOTOR_CALIBRATION->value)]
		private Types\Payloads\MotorCalibration $motorCalibration,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::MOTOR_CALIBRATION;
	}

	public function getState(): array
	{
		return [
			Types\Attribute::MOTOR_CALIBRATION->value => $this->motorCalibration,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->motorCalibration->value,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::MOTOR_CALIBRATION->value} = $this->motorCalibration->value;

		return $json;
	}

}
