<?php declare(strict_types = 1);

/**
 * DeviceEvent.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           12.01.23
 */

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;
use function array_map;

/**
 * Generation 2 device event message
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class DeviceEvent implements API\Messages\Message
{

	/**
	 * @param array<int, ComponentEvent> $events
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(class: ComponentEvent::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $events = [],
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
