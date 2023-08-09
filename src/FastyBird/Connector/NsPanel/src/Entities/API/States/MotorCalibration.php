<?php declare(strict_types = 1);

/**
 * MotorCalibration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\API\States;

use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Motor calibration detection capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class MotorCalibration implements State
{

	public function __construct(
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\MotorCalibrationPayload::class)]
		#[ObjectMapper\Modifiers\FieldName(Types\Protocol::MOTOR_CALIBRATION)]
		private readonly Types\MotorCalibrationPayload $motorCalibration,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::MOTOR_CALIBRATION);
	}

	public function getProtocols(): array
	{
		return [
			Types\Protocol::MOTOR_CALIBRATION => $this->motorCalibration,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->motorCalibration->getValue(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Protocol::MOTOR_CALIBRATION} = $this->motorCalibration->getValue();

		return $json;
	}

}
