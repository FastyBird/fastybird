<?php declare(strict_types = 1);

/**
 * SetDeviceStatus.php
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
use Nette;
use stdClass;

/**
 * Gateway report set device status response definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SetDeviceStatus implements Entities\API\Entity
{

	use Nette\SmartObject;

	public function __construct(private readonly SetDeviceStatusEvent $event)
	{
	}

	public function getEvent(): SetDeviceStatusEvent
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
		$json->event = $this->getEvent();

		return $json;
	}

}
