<?php declare(strict_types = 1);

/**
 * DeviceInputStatus.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           22.12.22
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen1;

use FastyBird\Connector\Shelly\Entities\API\Entity;
use Nette;

/**
 * Generation 1 device input status entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceInputStatus implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly int $input,
		private readonly string $event,
		private readonly int $eventCnt,
		private readonly string|null $lastSequence = null,
	)
	{
	}

	public function getInput(): int
	{
		return $this->input;
	}

	public function getEvent(): string
	{
		return $this->event;
	}

	public function getEventCnt(): int
	{
		return $this->eventCnt;
	}

	public function getLastSequence(): string|null
	{
		return $this->lastSequence;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'input' => $this->getInput(),
			'event' => $this->getEvent(),
			'event_count' => $this->getEventCnt(),
			'last_sequence' => $this->getLastSequence(),
		];
	}

}
