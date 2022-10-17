<?php declare(strict_types = 1);

/**
 * IChannelPropertiesManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          0.9.0
 *
 * @date           09.01.22
 */

namespace FastyBird\DevicesModule\Models\States;

use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\States;
use FastyBird\Metadata\Entities as MetadataEntities;
use Nette\Utils;

/**
 * Channel properties manager interface
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IChannelPropertiesManager
{

	public function create(
		MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped $property,
		Utils\ArrayHash $values,
	): States\ChannelProperty;

	public function update(
		MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped $property,
		States\ChannelProperty $state,
		Utils\ArrayHash $values,
	): States\ChannelProperty;

	public function delete(
		MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped $property,
		States\ChannelProperty $state,
	): bool;

}
