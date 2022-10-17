<?php declare(strict_types = 1);

/**
 * StateEntityCreated.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 * @since          0.65.0
 *
 * @date           22.06.22
 */

namespace FastyBird\DevicesModule\Events;

use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\States;
use FastyBird\Metadata\Entities as MetadataEntities;
use Symfony\Contracts\EventDispatcher;

/**
 * State entity was created event
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StateEntityCreated extends EventDispatcher\Event
{

	private States\DeviceProperty|States\ChannelProperty|States\ConnectorProperty $state;

	public function __construct(
		private readonly MetadataEntities\DevicesModule\DynamicProperty|MetadataEntities\DevicesModule\VariableProperty|MetadataEntities\DevicesModule\MappedProperty|Entities\Property $property,
		States\ConnectorProperty|States\ChannelProperty|States\DeviceProperty $state,
	)
	{
		$this->state = $state;
	}

	public function getProperty(): MetadataEntities\DevicesModule\DynamicProperty|MetadataEntities\DevicesModule\VariableProperty|MetadataEntities\DevicesModule\MappedProperty|Entities\Property
	{
		return $this->property;
	}

	public function getState(): States\ConnectorProperty|States\ChannelProperty|States\DeviceProperty
	{
		return $this->state;
	}

}
