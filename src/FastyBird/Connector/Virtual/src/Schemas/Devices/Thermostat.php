<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Connector\Virtual\Schemas\Devices;

use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Schemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;

/**
 * Thermostat device entity schema
 *
 * @template T of Entities\Devices\Thermostat
 * @extends  Schemas\VirtualDevice<T>
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Thermostat extends Schemas\VirtualDevice
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL . '/device/' . Entities\Devices\Thermostat::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Devices\Thermostat::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
