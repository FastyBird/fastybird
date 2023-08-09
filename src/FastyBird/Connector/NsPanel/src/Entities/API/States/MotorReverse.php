<?php declare(strict_types = 1);

/**
 * MotorReverse.php
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
use Orisai\ObjectMapper;
use stdClass;

/**
 * Motor reverse rotation capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class MotorReverse implements State
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName(Types\Protocol::MOTOR_REVERSE)]
		private readonly bool $motorReverse,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::MOTOR_REVERSE);
	}

	public function getProtocols(): array
	{
		return [
			Types\Protocol::MOTOR_REVERSE => $this->motorReverse,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->motorReverse,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Protocol::MOTOR_REVERSE} = $this->motorReverse;

		return $json;
	}

}
