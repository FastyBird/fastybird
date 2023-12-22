<?php declare(strict_types = 1);

/**
 * DeviceEvents.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           12.01.23
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use FastyBird\Connector\Shelly\Entities;
use Orisai\ObjectMapper;
use function array_map;

/**
 * Generation 2 device event entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceEvents implements Entities\API\Entity
{

	/**
	 * @param array<int, ComponentEvent> $events
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(class: ComponentEvent::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private readonly array $events = [],
	)
	{
	}

	/**
	 * @return array<ComponentEvent>
	 */
	public function getEvents(): array
	{
		return $this->events;
	}

	public function toArray(): array
	{
		return [
			'events' => array_map(
				static fn (ComponentEvent $state): array => $state->toArray(),
				$this->getEvents(),
			),
		];
	}

}
