<?php declare(strict_types = 1);

/**
 * ShellyConnector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           22.01.22
 */

namespace FastyBird\Connector\Shelly\Schemas;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Shelly connector entity schema
 *
 * @extends DevicesSchemas\Connectors\Connector<Entities\ShellyConnector>
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ShellyConnector extends DevicesSchemas\Connectors\Connector
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Connector::SHELLY . '/connector/' . Entities\ShellyConnector::TYPE;

	public function getEntityClass(): string
	{
		return Entities\ShellyConnector::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
