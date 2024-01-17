<?php declare(strict_types = 1);

/**
 * Blank.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @since          1.0.0
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
 * @template T of Entities\Devices\Blank
 * @extends Device<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Blank extends Device
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ModuleSource::DEVICES . '/device/' . Entities\Devices\Blank::DEVICE_TYPE;

	public function getEntityClass(): string
	{
		return Entities\Devices\Blank::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
