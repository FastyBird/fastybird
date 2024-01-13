<?php declare(strict_types = 1);

/**
 * Preset.php
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
 * Virtual thermostat preset channel entity schema
 *
 * @template T of Entities\Channels\Preset
 * @extends  Schemas\ThermostatChannel<T>
 *
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Preset extends Schemas\ThermostatChannel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL . '/channel/' . Entities\Channels\Preset::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Preset::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
