<?php declare(strict_types = 1);

/**
 * SetDeviceState.php
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

namespace FastyBird\Connector\NsPanel\Entities\API\Response;

use FastyBird\Connector\NsPanel\Entities;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Gateway report set device state response definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SetDeviceState implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(SetDeviceStateEvent::class)]
		private readonly SetDeviceStateEvent $event,
	)
	{
	}

	public function getEvent(): SetDeviceStateEvent
	{
		return $this->event;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'event' => $this->getEvent(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->event = $this->getEvent()->toJson();

		return $json;
	}

}
