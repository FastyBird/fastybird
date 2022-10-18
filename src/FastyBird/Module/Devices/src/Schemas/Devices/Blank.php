<?php declare(strict_types = 1);

/**
 * Blank.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @since          0.9.0
 *
 * @date           07.01.22
 */

namespace FastyBird\Module\Devices\Schemas\Devices;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Schemas;

/**
 * Blank device entity schema
 *
 * @extends Device<Entities\Devices\Blank>
 *
 * @package         FastyBird:DevicesModule!
 * @subpackage      Schemas
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Blank extends Device
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES . '/device/' . Entities\Devices\Blank::DEVICE_TYPE;

	public function getEntityClass(): string
	{
		return Entities\Devices\Blank::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
