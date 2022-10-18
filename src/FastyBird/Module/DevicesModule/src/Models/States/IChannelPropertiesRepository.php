<?php declare(strict_types = 1);

/**
 * IDevicePropertiesRepository.php
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
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use Ramsey\Uuid;

/**
 * Channel property repository interface
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IChannelPropertiesRepository
{

	public function findOne(
		MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped $property,
	): States\ChannelProperty|null;

	public function findOneById(
		Uuid\UuidInterface $id,
	): States\ChannelProperty|null;

}
