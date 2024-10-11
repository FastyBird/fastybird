<?php declare(strict_types = 1);

/**
 * MotorReverse.php
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
 * Motor reverse rotation capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class MotorReverse implements State
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::MOTOR_REVERSE->value)]
		private bool $motorReverse,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::MOTOR_REVERSE;
	}

	public function getState(): array
	{
		return [
			Types\Attribute::MOTOR_REVERSE->value => $this->motorReverse,
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
		$json->{Types\Attribute::MOTOR_REVERSE->value} = $this->motorReverse;

		return $json;
	}

}
