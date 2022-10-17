<?php declare(strict_types = 1);

/**
 * Blank.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @since          0.6.0
 *
 * @date           07.12.21
 */

namespace FastyBird\DevicesModule\Schemas\Connectors;

use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Schemas;
use FastyBird\Metadata\Types as MetadataTypes;

/**
 * Modbus connector entity schema
 *
 * @extends Connector<Entities\Connectors\Blank>
 *
 * @package         FastyBird:DevicesModule!
 * @subpackage      Schemas
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Blank extends Connector
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES . '/connector/' . Entities\Connectors\Blank::CONNECTOR_TYPE;

	public function getEntityClass(): string
	{
		return Entities\Connectors\Blank::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
