<?php declare(strict_types = 1);

/**
 * ReportDeviceState.php
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

namespace FastyBird\Connector\NsPanel\Entities\API\Request;

use FastyBird\Connector\NsPanel\Entities;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Report third-party device state to NS Panel request definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ReportDeviceState implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(ReportDeviceStateEvent::class)]
		private readonly ReportDeviceStateEvent $event,
	)
	{
	}

	public function getEvent(): ReportDeviceStateEvent
	{
		return $this->event;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'event' => $this->getEvent()->toArray(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->event = $this->getEvent()->toJson();

		return $json;
	}

}
