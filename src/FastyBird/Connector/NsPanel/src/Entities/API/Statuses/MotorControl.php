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
final class MotorControl implements Status, ObjectMapper\MappedObject
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $motorControl,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::MOTOR_CONTROL);
	}

	public function getName(): string|null
	{
		return null;
	}

	public function getValue(): Types\MotorControlPayload
	{
		return Types\MotorControlPayload::get($this->motorControl);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			$this->getType()->getValue() => $this->getValue()->getValue(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->motorControl = $this->getValue()->getValue();

		return $json;
	}

}
