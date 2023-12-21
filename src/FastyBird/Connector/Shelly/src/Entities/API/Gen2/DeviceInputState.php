<?php declare(strict_types = 1);

/**
 * DeviceInputState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           26.12.22
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;
use function array_merge;
use function is_bool;
use function is_int;

/**
 * Generation 2 device input state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceInputState extends DeviceState implements Entities\API\Entity
{

	/**
	 * @param array<string> $errors
	 */
	public function __construct(
		int $id,
		#[ObjectMapper\Rules\AnyOf([
			new BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\InputPayload::class),
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly Types\InputPayload|bool|null $state,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $percent,
		array $errors = [],
	)
	{
		parent::__construct($id, $errors);
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::get(Types\ComponentType::INPUT);
	}

	public function getState(): Types\InputPayload|bool|null
	{
		return $this->state;
	}

	public function getPercent(): int|null
	{
		return $this->percent;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'state' => $this->getState() instanceof Types\InputPayload ? $this->getState()->getValue() : $this->getState(),
				'percent' => $this->getPercent(),
			],
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toState(): array
	{
		return array_merge(
			parent::toState(),
			$this->getState() instanceof Types\InputPayload ? ['button' => $this->getState()->getValue()] : [],
			is_bool($this->getState()) ? ['switch' => $this->getState()] : [],
			is_int($this->getPercent()) ? ['analog' => $this->getPercent()] : [],
		);
	}

}
