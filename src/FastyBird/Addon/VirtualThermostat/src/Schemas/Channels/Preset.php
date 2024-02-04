<?php declare(strict_types = 1);

/**
 * Preset.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           23.10.23
 */

namespace FastyBird\Addon\VirtualThermostat\Schemas\Channels;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Addon\VirtualThermostat\Schemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;

/**
 * Preset channel entity schema
 *
 * @template T of Entities\Channels\Preset
 * @extends  Schemas\ThermostatChannel<T>
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Preset extends Schemas\ThermostatChannel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::VIRTUAL . '/channel/' . Entities\Channels\Preset::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Preset::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
