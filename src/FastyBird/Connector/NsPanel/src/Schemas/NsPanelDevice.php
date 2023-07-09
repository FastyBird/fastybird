<?php declare(strict_types = 1);

/**
 * NsPanelDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Schemas;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * NS Panel connector entity schema
 *
 * @extends DevicesSchemas\Devices\Device<Entities\NsPanelDevice>
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class NsPanelDevice extends DevicesSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL . '/device/' . Entities\NsPanelDevice::DEVICE_TYPE;

	public function getEntityClass(): string
	{
		return Entities\NsPanelDevice::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
