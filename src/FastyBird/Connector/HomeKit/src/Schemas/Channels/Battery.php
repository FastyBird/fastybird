<?php declare(strict_types = 1);

/**
 * Battery.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           30.01.24
 */

namespace FastyBird\Connector\HomeKit\Schemas\Channels;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Schemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;

/**
 * Battery channel entity schema
 *
 * @template T of Entities\Channels\Battery
 * @extends  Schemas\HomeKitChannel<T>
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Battery extends Schemas\HomeKitChannel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Connector::HOMEKIT . '/channel/' . Entities\Channels\Battery::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Battery::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
