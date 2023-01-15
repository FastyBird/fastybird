<?php declare(strict_types = 1);

/**
 * DeviceEvent.php
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
use Nette;
use function array_map;

/**
 * Generation 2 device event entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceEvent implements Entities\API\Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<int, ComponentEvent> $events
	 */
	public function __construct(private readonly array $events = [])
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
				static fn (ComponentEvent $status): array => $status->toArray(),
				$this->getEvents(),
			),
		];
	}

}
