<?php declare(strict_types = 1);

/**
 * IConnectorPropertiesManager.php
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

namespace FastyBird\Module\Devices\Models\States;

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\States;
use Nette\Utils;

/**
 * Connector properties manager interface
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IConnectorPropertiesManager
{

	public function create(
		MetadataEntities\DevicesModule\ConnectorDynamicProperty|MetadataEntities\DevicesModule\ConnectorMappedProperty|Entities\Connectors\Properties\Dynamic $property,
		Utils\ArrayHash $values,
	): States\ConnectorProperty;

	public function update(
		MetadataEntities\DevicesModule\ConnectorDynamicProperty|MetadataEntities\DevicesModule\ConnectorMappedProperty|Entities\Connectors\Properties\Dynamic $property,
		States\ConnectorProperty $state,
		Utils\ArrayHash $values,
	): States\ConnectorProperty;

	public function delete(
		MetadataEntities\DevicesModule\ConnectorDynamicProperty|MetadataEntities\DevicesModule\ConnectorMappedProperty|Entities\Connectors\Properties\Dynamic $property,
		States\ConnectorProperty $state,
	): bool;

}
