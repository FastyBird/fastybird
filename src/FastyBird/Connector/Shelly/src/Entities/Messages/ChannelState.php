<?php declare(strict_types = 1);

/**
 * ChannelState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           18.07.22
 */

namespace FastyBird\Connector\Shelly\Entities\Messages;

use Orisai\ObjectMapper;
use function array_map;
use function array_unique;
use const SORT_REGULAR;

/**
 * Device channel state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelState implements Entity
{

	/** @var array<PropertyState> */
	private array $filteredSensors;

	/**
	 * @param array<PropertyState> $sensors
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $identifier,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(PropertyState::class),
		)]
		private readonly array $sensors = [],
	)
	{
		$this->filteredSensors = array_unique($this->sensors, SORT_REGULAR);
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/**
	 * @return array<PropertyState>
	 */
	public function getSensors(): array
	{
		return $this->filteredSensors;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'sensors' => array_map(
				static fn (PropertyState $sensor): array => $sensor->toArray(),
				$this->getSensors(),
			),
		];
	}

}
