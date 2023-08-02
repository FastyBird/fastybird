<?php declare(strict_types = 1);

/**
 * MotorControl.php
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

namespace FastyBird\Connector\NsPanel\Entities\API\Statuses;

use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Motor control capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class MotorControl implements Status
{

	public function __construct(
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\MotorControlPayload::class)]
		#[ObjectMapper\Modifiers\FieldName(Types\Protocol::MOTOR_CONTROL)]
		private readonly Types\MotorControlPayload $value,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::MOTOR_CONTROL);
	}

	public function getValue(): Types\MotorControlPayload
	{
		return $this->value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->getValue()->getValue(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Protocol::MOTOR_CONTROL} = $this->getValue()->getValue();

		return $json;
	}

}
