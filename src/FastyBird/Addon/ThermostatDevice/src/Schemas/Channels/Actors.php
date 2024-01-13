<?php declare(strict_types = 1);

/**
 * Actors.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           23.10.23
 */

namespace FastyBird\Addon\ThermostatDevice\Schemas\Channels;

use FastyBird\Addon\ThermostatDevice\Entities;
use FastyBird\Addon\ThermostatDevice\Schemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;

/**
 * Actors channel entity schema
 *
 * @template T of Entities\Channels\Actors
 * @extends  Schemas\ThermostatChannel<T>
 *
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Actors extends Schemas\ThermostatChannel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL . '/channel/' . Entities\Channels\Actors::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Actors::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
