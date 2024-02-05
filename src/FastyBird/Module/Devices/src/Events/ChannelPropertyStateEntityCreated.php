<?php declare(strict_types = 1);

/**
 * ChannelPropertyStateEntityCreated.php
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
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\States;
use Symfony\Contracts\EventDispatcher;

/**
 * Channel property state entity was created event
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ChannelPropertyStateEntityCreated extends EventDispatcher\Event
{

	public function __construct(
		private readonly MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty $property,
		private readonly States\ChannelProperty $read,
		private readonly States\ChannelProperty $get,
		private readonly MetadataTypes\Sources\Source $source,
	)
	{
	}

	public function getProperty(): MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty
	{
		return $this->property;
	}

	public function getRead(): States\ChannelProperty
	{
		return $this->read;
	}

	public function getGet(): States\ChannelProperty
	{
		return $this->get;
	}

	public function getSource(): MetadataTypes\Sources\Source
	{
		return $this->source;
	}

}
