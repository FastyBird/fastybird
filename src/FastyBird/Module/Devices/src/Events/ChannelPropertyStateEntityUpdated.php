<?php declare(strict_types = 1);

/**
 * ChannelPropertyStateEntityUpdated.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           29.07.23
 */

namespace FastyBird\Module\Devices\Events;

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\States;
use Symfony\Contracts\EventDispatcher;

/**
 * Channel property state entity was updated event
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ChannelPropertyStateEntityUpdated extends EventDispatcher\Event
{

	public function __construct(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		private readonly MetadataEntities\DevicesModule\ChannelDynamicProperty|Entities\Channels\Properties\Dynamic|MetadataEntities\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Mapped $property,
		private readonly States\ChannelProperty $previousState,
		private readonly States\ChannelProperty $state,
	)
	{
	}
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getProperty(): MetadataEntities\DevicesModule\ChannelDynamicProperty|Entities\Channels\Properties\Dynamic|MetadataEntities\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Mapped
	{
		return $this->property;
	}

	public function getPreviousState(): States\ChannelProperty
	{
		return $this->previousState;
	}

	public function getState(): States\ChannelProperty
	{
		return $this->state;
	}

}
