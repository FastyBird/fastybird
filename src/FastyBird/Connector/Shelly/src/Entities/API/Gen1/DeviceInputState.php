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
 * @date           22.12.22
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen1;

use FastyBird\Connector\Shelly\Entities;
use Orisai\ObjectMapper;

/**
 * Generation 1 device input state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceInputState implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue()]
		private readonly int $input,
		#[ObjectMapper\Rules\StringValue()]
		private readonly string $event,
		#[ObjectMapper\Rules\IntValue()]
		#[ObjectMapper\Modifiers\FieldName('event_count')]
		private readonly int $eventCount,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('last_sequence')]
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
		return $this->eventCount;
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
