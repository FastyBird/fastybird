<?php declare(strict_types = 1);

/**
 * IDevicePropertiesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          0.31.0
 *
 * @date           08.02.22
 */

namespace FastyBird\DevicesModule\Models\States;

use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\States;
use FastyBird\Metadata\Entities as MetadataEntities;
use Ramsey\Uuid;

/**
 * Connector property repository interface
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IConnectorPropertiesRepository
{

	public function findOne(
		MetadataEntities\DevicesModule\ConnectorDynamicProperty|MetadataEntities\DevicesModule\ConnectorMappedProperty|Entities\Connectors\Properties\Dynamic $property,
	): States\ConnectorProperty|null;

	public function findOneById(
		Uuid\UuidInterface $id,
	): States\ConnectorProperty|null;

}
