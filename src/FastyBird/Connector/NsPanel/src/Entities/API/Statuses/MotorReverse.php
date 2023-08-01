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

namespace FastyBird\Connector\NsPanel\Entities\API\Statuses;

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
final class MotorReverse implements Status, ObjectMapper\MappedObject
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $motorReverse,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::MOTOR_REVERSE);
	}

	public function getName(): string|null
	{
		return null;
	}

	public function getValue(): bool
	{
		return $this->motorReverse;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			$this->getType()->getValue() => $this->getValue(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->motorReverse = $this->getValue();

		return $json;
	}

}
