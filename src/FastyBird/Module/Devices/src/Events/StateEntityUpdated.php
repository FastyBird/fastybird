<?php declare(strict_types = 1);

/**
 * StateEntityUpdated.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           22.06.22
 */

namespace FastyBird\Module\Devices\Events;

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\States;
use Symfony\Contracts\EventDispatcher;

/**
 * State entity was updated event
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StateEntityUpdated extends EventDispatcher\Event
{

	public function __construct(
		private readonly MetadataEntities\DevicesModule\DynamicProperty|Entities\Connectors\Properties\Dynamic|Entities\Devices\Properties\Dynamic|Entities\Channels\Properties\Dynamic $property,
		private readonly States\ConnectorProperty|States\ChannelProperty|States\DeviceProperty $previousState,
		private readonly States\ConnectorProperty|States\ChannelProperty|States\DeviceProperty $state,
	)
	{
	}

	public function getProperty(): MetadataEntities\DevicesModule\DynamicProperty|Entities\Connectors\Properties\Dynamic|Entities\Devices\Properties\Dynamic|Entities\Channels\Properties\Dynamic
	{
		return $this->property;
	}

	public function getPreviousState(): States\ConnectorProperty|States\ChannelProperty|States\DeviceProperty
	{
		return $this->previousState;
	}

	public function getState(): States\ConnectorProperty|States\ChannelProperty|States\DeviceProperty
	{
		return $this->state;
	}

}
