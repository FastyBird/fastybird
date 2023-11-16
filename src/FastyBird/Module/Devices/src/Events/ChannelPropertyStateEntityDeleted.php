<?php declare(strict_types = 1);

/**
 * ChannelPropertyStateEntityDeleted.php
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

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Module\Devices\Entities;
use Symfony\Contracts\EventDispatcher;

/**
 * Channel property state entity was deleted event
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ChannelPropertyStateEntityDeleted extends EventDispatcher\Event
{

	public function __construct(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		private readonly MetadataDocuments\DevicesModule\ChannelDynamicProperty|Entities\Channels\Properties\Dynamic|MetadataDocuments\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Mapped $property,
	)
	{
	}
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getProperty(): MetadataDocuments\DevicesModule\ChannelDynamicProperty|Entities\Channels\Properties\Dynamic|MetadataDocuments\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Mapped
	{
		return $this->property;
	}

}
